<?php

use App\Models\{Account, AccountBalance, AccountCategory, AccountSubCategory, Currency, Setting, Transaction, User};
use Illuminate\Support\Facades\{Artisan, DB};
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::create(['name' => 'Manager', 'email' => 'manager@example.test', 'username' => 'manager', 'password' => 'password', 'account_type' => 'admin']);
    $this->actingAs($this->user);
    foreach (['update settings', 'view dashboard'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->user->givePermissionTo(['update settings', 'view dashboard']);
    $category = AccountCategory::create(['name' => 'Accounts', 'code_prefix' => '#']);
    $sub = AccountSubCategory::create(['name' => 'Customer', 'account_category_id' => $category->id]);
    $this->account = Account::create(['name' => 'Customer', 'code' => '#1', 'account_sub_category_id' => $sub->id, 'is_active' => true, 'include_in_reports' => true]);
    $this->currency = Currency::create(['name' => 'Dollar', 'code' => 'USD', 'is_active' => true]);
    Setting::create(['company_name' => 'Test', 'currency' => $this->currency->id]);
    $this->entry = [
        'account_id' => $this->account->id, 'currency_id' => $this->currency->id,
        'payment_currency_id' => $this->currency->id, 'payment_amount' => 100,
        'actual_currency_id' => $this->currency->id, 'actual_amount' => 100,
        'amount' => 100, 'transaction_type' => 'credit', 'status' => 'active',
        'created_by' => $this->user->id, 'is_visible' => true,
    ];
});

it('rebuilds only active ledger balances and zeros stale pairs repeatedly', function () {
    DB::table('transactions')->insert($this->entry);
    DB::table('transactions')->insert(array_replace($this->entry, ['amount' => 25, 'transaction_type' => 'debit', 'is_visible' => false]));
    DB::table('transactions')->insert(array_replace($this->entry, ['amount' => 900, 'status' => 'inactive']));
    DB::table('transactions')->insert(array_replace($this->entry, ['amount' => 800, 'status' => 'pending']));
    $other = Currency::create(['name' => 'Afghani', 'code' => 'AFN', 'is_active' => true]);
    AccountBalance::create(['account_id' => $this->account->id, 'currency_id' => $other->id, 'credit' => 999, 'debit' => 0, 'balance' => 999]);
    foreach ([1, 2] as $attempt) {
        $this->artisan('balances:recalculate')->assertSuccessful();
        $row = AccountBalance::where('currency_id', $this->currency->id)->first();
        expect((float) $row->credit)->toBe(100.0)->and((float) $row->debit)->toBe(25.0)->and((float) $row->balance)->toBe(75.0);
        expect((float) AccountBalance::where('currency_id', $other->id)->value('balance'))->toBe(0.0);
    }
    $this->get(route('admin.dashboard', ['currency_id' => $this->currency->id]))
        ->assertOk()->assertSee('refreshSystemBalances')
        ->assertViewHas('customerBalances', fn ($rows) => (float) $rows->first()->balance === 75.0);
});

it('keeps cached balances synchronized with edited amounts, direction, status and currency', function () {
    $entry = Transaction::create($this->entry);
    $balance = fn () => (float) AccountBalance::where('currency_id', $this->currency->id)->value('balance');
    expect($balance())->toBe(100.0);
    $entry->update(['amount' => 250]);
    expect($balance())->toBe(250.0);
    $entry->update(['transaction_type' => 'debit']);
    expect($balance())->toBe(-250.0);
    $entry->update(['status' => 'inactive']);
    expect($balance())->toBe(0.0);
    $entry->update(['status' => 'active']);
    $other = Currency::create(['name' => 'Afghani', 'code' => 'AFN', 'is_active' => true]);
    $entry->update(['currency_id' => $other->id]);
    expect($balance())->toBe(0.0);
    expect((float) AccountBalance::where('currency_id', $other->id)->value('balance'))->toBe(-250.0);
    $entry->delete();
    expect((float) AccountBalance::where('currency_id', $other->id)->value('balance'))->toBe(0.0);
    Transaction::create(array_replace($this->entry, ['status' => 'pending']))->delete();
    expect($balance())->toBe(0.0);
});

it('clears caches before rebuilding balances through the authorized POST action', function () {
    Artisan::shouldReceive('call')->once()->with('optimize:clear')->ordered()->andReturn(0);
    Artisan::shouldReceive('call')->once()->with('balances:recalculate')->ordered()->andReturn(0);
    $this->postJson(route('admin.dashboard.refresh-balances'))->assertOk()
        ->assertJsonPath('message', __('ui.dashboard_refresh.success'));
});

it('denies maintenance without settings permission', function () {
    $this->user->revokePermissionTo('update settings');
    Artisan::shouldReceive('call')->never();
    $this->postJson(route('admin.dashboard.refresh-balances'))->assertForbidden();
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('id="refreshSystemBalances"', false);
});

it('reports command failures without claiming success', function (string $command) {
    Artisan::shouldReceive('call')->once()->with('optimize:clear')->andReturn($command === 'optimize:clear' ? 1 : 0);
    if ($command === 'balances:recalculate') {
        Artisan::shouldReceive('call')->once()->with('balances:recalculate')->andReturn(1);
    }
    $this->postJson(route('admin.dashboard.refresh-balances'))->assertStatus(500)
        ->assertJsonPath('message', __('ui.dashboard_refresh.failed'));
})->with(['optimize:clear', 'balances:recalculate']);
