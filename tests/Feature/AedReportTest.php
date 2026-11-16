<?php

use App\Models\{Account, AccountCategory, AccountSubCategory, AedReportEntry, Currency, Transaction, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-01-02 12:00:00'));
    $this->withoutMiddleware(\App\Http\Middleware\CheckPermissionWithFeedback::class);
    $this->user = User::create(['name' => 'Tester', 'email' => 'aed@example.test', 'username' => 'aedtester', 'password' => 'password', 'account_type' => 'admin']);
    $this->actingAs($this->user);
    $category = AccountCategory::create(['name' => 'Client', 'code_prefix' => '#']);
    $sub = AccountSubCategory::create(['name' => 'Customer', 'account_category_id' => $category->id]);
    $this->account = Account::create(['name' => 'AED Customer', 'code' => '#AED', 'account_sub_category_id' => $sub->id]);
    $this->aed = Currency::create(['name' => 'Dirham', 'code' => 'AED']);
    \App\Models\Setting::create(['company_name' => 'Test', 'currency' => $this->aed->id]);
    $this->ledger = function ($amount, $type = 'credit', $status = 'active', $date = '2026-01-01') {
        return Transaction::withoutEvents(fn () => Transaction::create([
            'account_id' => $this->account->id, 'currency_id' => $this->aed->id,
            'payment_currency_id' => $this->aed->id, 'actual_currency_id' => $this->aed->id,
            'payment_amount' => $amount, 'actual_amount' => $amount, 'amount' => $amount,
            'transaction_type' => $type, 'status' => $status, 'created_by' => $this->user->id,
            'created_at' => $date,
        ]));
    };
    $this->payload = ['account_ids' => [$this->account->id], 'exchange_rate' => '4', 'date' => '2026-01-02'];
});

it('saves signed active ledger balances through the selected date and renders totals', function () {
    ($this->ledger)(100);
    ($this->ledger)(300, 'debit');
    ($this->ledger)(900, 'credit', 'inactive');
    ($this->ledger)(900, 'credit', 'pending');
    ($this->ledger)(900, 'credit', 'active', '2026-01-03');
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('aed_report_entries', ['account_id' => $this->account->id, 'currency_id' => $this->aed->id, 'aed_balance' => -200, 'exchange_rate' => 3.67, 'converted_amount' => -54.5]);
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('-200.00 AED')->assertSee('-54.50 USD')->assertSee('Exchange Rate');
    ($this->ledger)(1000);
    expect((float) AedReportEntry::first()->aed_balance)->toBe(-200.0);
});

it('rejects duplicate accounts and leaves the saved snapshot unchanged', function () {
    ($this->ledger)(100);
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasErrors('account_ids');
    expect(AedReportEntry::count())->toBe(1);
    $this->delete(route('admin.reports.aed.destroy', AedReportEntry::first()))->assertRedirect();
    expect(AedReportEntry::count())->toBe(0);
});

it('lists all unsaved accounts in subcategories one and two and permits zero balances', function () {
    $categoryId = $this->account->subCategory->account_category_id;
    AccountSubCategory::create(['id' => 2, 'name' => 'Saraf', 'account_category_id' => $categoryId]);
    AccountSubCategory::create(['id' => 3, 'name' => 'Safe', 'account_category_id' => $categoryId]);
    $saraf = Account::create(['name' => 'Saraf', 'code' => '#SARAF', 'account_sub_category_id' => 2, 'is_active' => false]);
    $safe = Account::create(['name' => 'Safe', 'code' => '#SAFE', 'account_sub_category_id' => 3]);
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('Select Accounts')->assertViewHas('customers', fn ($accounts) => $accounts->pluck('id')->sort()->values()->all() === [$this->account->id, $saraf->id]);
    $this->post(route('admin.reports.aed.store'), ['account_ids' => [$safe->id]])->assertSessionHasErrors('account_ids');
    $this->post(route('admin.reports.aed.store'), ['account_ids' => [$saraf->id]])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('aed_report_entries', ['account_id' => $saraf->id, 'aed_balance' => 0, 'converted_amount' => 0]);
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertViewHas('customers', fn ($accounts) => $accounts->pluck('id')->all() === [$this->account->id]);
});

it('ignores submitted conversion rates and uses today', function () {
    ($this->ledger)(367);
    $this->post(route('admin.reports.aed.store'), array_replace($this->payload, ['exchange_rate' => '0', 'date' => '2000-01-01']))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('aed_report_entries', ['exchange_rate' => 3.67, 'converted_amount' => 100]);
    expect(AedReportEntry::first()->date->toDateString())->toBe('2026-01-02');
});

it('stores multiple checked accounts without a rate or date input', function () {
    ($this->ledger)(367);
    $second = $this->account->replicate();
    $second->code = '#SECOND';
    $second->save();
    $transaction = Transaction::first()->replicate();
    $transaction->account_id = $second->id;
    Transaction::withoutEvents(fn () => $transaction->save());
    $this->post(route('admin.reports.aed.store'), ['account_ids' => [$this->account->id, $second->id]])->assertSessionHasNoErrors();
    expect(AedReportEntry::count())->toBe(2);
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('734.00 AED')->assertSee('200.00 USD')->assertSee('id="aed-add-form" class="card mb-3"  hidden', false);
});

it('rejects repeated selection', function () {
    ($this->ledger)(100);
    $this->post(route('admin.reports.aed.store'), ['account_ids' => [$this->account->id, $this->account->id]])->assertSessionHasErrors('account_ids.0');
});

it('protects every report route with authentication and report permission', function () {
    foreach (['index', 'store', 'update', 'destroy', 'pdf'] as $action) {
        $middleware = app('router')->getRoutes()->getByName('admin.reports.aed.'.$action)->gatherMiddleware();
        expect($middleware)->toContain('auth', 'permission.feedback:view customers reports');
    }
});

it('enforces uniqueness at the database level', function () {
    ($this->ledger)(100);
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $entry = AedReportEntry::first();
    expect(fn () => $entry->replicate()->save())->toThrow(\Illuminate\Database\QueryException::class);
});

it('exports the saved list as a PDF with matching totals', function () {
    ($this->ledger)(367);
    $this->post(route('admin.reports.aed.store'), ['account_ids' => [$this->account->id]])->assertSessionHasNoErrors();
    $this->get(route('admin.reports.aed.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $response = $this->get(route('admin.reports.aed.pdf'));
    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
    $this->view('admin.reports.exports.aed_pdf', ['entries' => AedReportEntry::with('account')->get(), 'company' => null, 'generatedAt' => now()])
        ->assertSee('367.00 AED')->assertSee('100.00 USD')->assertSee('Exchange Rate')->assertSee('AED Customer (#AED)');
});

it('uses actual AED amounts regardless of the posting currency', function () {
    $usd = Currency::create(['name' => 'Dollar', 'code' => 'USD']);
    $credit = ($this->ledger)(100);
    $debit = ($this->ledger)(20, 'debit');
    $excluded = ($this->ledger)(900);
    Transaction::withoutEvents(function () use ($usd, $credit, $debit, $excluded) {
        $credit->update(['currency_id' => $usd->id, 'actual_amount' => 734]);
        $debit->update(['currency_id' => $usd->id, 'actual_amount' => 367]);
        $excluded->update(['actual_currency_id' => $usd->id]);
    });
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('aed_report_entries', ['account_id' => $this->account->id, 'aed_balance' => 367, 'converted_amount' => 80]);
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('367.00 AED')->assertSee('80.00 USD');
});

it('repairs existing snapshots using actual AED balances at each report date', function () {
    $usd = Currency::create(['name' => 'Dollar', 'code' => 'USD']);
    $credit = ($this->ledger)(100);
    Transaction::withoutEvents(fn () => $credit->update(['currency_id' => $usd->id, 'actual_amount' => 734]));
    ($this->ledger)(367, 'debit');
    ($this->ledger)(900, 'credit', 'inactive');
    ($this->ledger)(900, 'credit', 'pending');
    ($this->ledger)(900, 'credit', 'active', '2026-01-03');
    $excluded = ($this->ledger)(900);
    Transaction::withoutEvents(fn () => $excluded->update(['actual_currency_id' => $usd->id]));
    $entry = AedReportEntry::create([
        'account_id' => $this->account->id, 'currency_id' => $this->aed->id,
        'aed_balance' => 0, 'exchange_rate' => 3.67, 'converted_amount' => 100, 'date' => '2026-01-02',
    ]);
    $migration = require database_path('migrations/2026_10_24_000001_fix_aed_report_actual_balances.php');
    $migration->up();
    $migration->up();
    $entry->refresh();
    expect((float) $entry->aed_balance)->toBe(367.0)
        ->and((float) $entry->converted_amount)->toBe(100.0)
        ->and($entry->date->toDateString())->toBe('2026-01-02');
    $this->view('admin.reports.exports.aed_pdf', ['entries' => collect([$entry->load('account')]), 'company' => null, 'generatedAt' => now()])
        ->assertSee('367.00 AED')->assertSee('0.00 USD');
});


it('refreshes both sides after later transactions using the saved row rate', function () {
    ($this->ledger)(400);
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $entry = AedReportEntry::first();
    $this->patchJson(route('admin.reports.aed.update', $entry), ['exchange_rate' => '4'])
        ->assertOk()->assertJson(['exchange_rate' => '4.00000000', 'aed_balance' => '400.00', 'converted_amount' => '100.00']);
    $this->travelTo(\Carbon\Carbon::parse('2026-01-05 12:00:00'));
    $new = ($this->ledger)(200, 'credit', 'active', '2026-01-05');
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('600.00 AED')->assertSee('150.00 USD')->assertSee('2026-01-05');
    $this->view('admin.reports.exports.aed_pdf', ['entries' => AedReportEntry::with('account')->get(), 'company' => null, 'generatedAt' => now()])
        ->assertSee('600.00 AED')->assertSee('150.00 USD');
    Transaction::withoutEvents(fn () => $new->update(['status' => 'inactive']));
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('400.00 AED')->assertSee('100.00 USD');
    Transaction::withoutEvents(fn () => $new->update(['status' => 'active', 'actual_amount' => 300]));
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('700.00 AED')->assertSee('175.00 USD');
    expect((float) $entry->fresh()->exchange_rate)->toBe(4.0);
});

it('keeps row rates independent and totals the individually converted balances', function () {
    ($this->ledger)(400);
    $second = $this->account->replicate();
    $second->code = '#SECOND';
    $second->save();
    $transaction = Transaction::first()->replicate();
    $transaction->account_id = $second->id;
    Transaction::withoutEvents(fn () => $transaction->save());
    $this->post(route('admin.reports.aed.store'), ['account_ids' => [$this->account->id, $second->id]])->assertSessionHasNoErrors();
    $entries = AedReportEntry::orderBy('id')->get();
    foreach ([4, 5] as $index => $rate) {
        $this->patch(route('admin.reports.aed.update', $entries[$index]), ['exchange_rate' => $rate])->assertSessionHasNoErrors();
    }
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('800.00 AED')->assertSee('180.00 USD');
    expect((float) $entries[0]->fresh()->exchange_rate)->toBe(4.0)
        ->and((float) $entries[1]->fresh()->exchange_rate)->toBe(5.0);
});

it('rejects invalid rates without changing the saved rate', function ($rate) {
    ($this->ledger)(367);
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $entry = AedReportEntry::first();
    $this->patch(route('admin.reports.aed.update', $entry), ['exchange_rate' => $rate])->assertSessionHasErrors('exchange_rate');
    expect((float) $entry->fresh()->exchange_rate)->toBe(3.67);
})->with([0, -1, '', 'abc', '0.000000001', '1000000000000']);

it('rejects rate changes for a non AED report entry', function () {
    $usd = Currency::create(['name' => 'Dollar', 'code' => 'USD']);
    $entry = AedReportEntry::create(['account_id' => $this->account->id, 'currency_id' => $usd->id, 'aed_balance' => 0, 'converted_amount' => 0, 'exchange_rate' => 3.67, 'date' => now()]);
    $this->patch(route('admin.reports.aed.update', $entry), ['exchange_rate' => 4])->assertNotFound();
});

it('uses USD posting balances directly and converts only other posting currencies', function () {
    $usd = Currency::create(['name' => 'Dollar', 'code' => 'USD']);
    $credit = ($this->ledger)(150);
    $debit = ($this->ledger)(25, 'debit');
    Transaction::withoutEvents(function () use ($credit, $debit, $usd) {
        $credit->update(['currency_id' => $usd->id, 'actual_amount' => 600]);
        $debit->update(['currency_id' => $usd->id, 'actual_amount' => 100]);
    });
    ($this->ledger)(400);
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $entry = AedReportEntry::first();
    $this->patchJson(route('admin.reports.aed.update', $entry), ['exchange_rate' => 4])->assertOk()
        ->assertJson(['aed_balance' => '900.00', 'converted_amount' => '225.00', 'usd_balance' => 125, 'conversion_aed_balance' => 400]);
    $this->patchJson(route('admin.reports.aed.update', $entry), ['exchange_rate' => 5])->assertOk()
        ->assertJson(['converted_amount' => '205.00']);
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('205.00 USD');
    $this->view('admin.reports.exports.aed_pdf', ['entries' => AedReportEntry::with('account')->get(), 'company' => null, 'generatedAt' => now()])
        ->assertSee('900.00 AED')->assertSee('205.00 USD');
    Transaction::withoutEvents(fn () => $credit->update(['amount' => 175]));
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('230.00 USD');
});

it('derives the displayed rate from the report AED and converted USD balances', function () {
    ($this->ledger)(50000);
    ($this->ledger)(900, 'credit', 'inactive');
    ($this->ledger)(900, 'credit', 'pending');
    ($this->ledger)(900, 'credit', 'active', '2026-01-03');
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $entry = AedReportEntry::first();
    $entry->update(['exchange_rate' => 3.8]);
    $entries = AedReportEntry::with('account')->get();
    AedReportEntry::refreshLiveBalances($entries);
    expect((float) $entries->first()->aed_balance)->toBe(50000.0)
        ->and((float) $entries->first()->converted_amount)->toBe(13157.89)
        ->and($entries->first()->calculated_exchange_rate)->toBe(3.8);
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('>3.8</td>', false)->assertSee('13,157.89')->assertDontSee('aed-rate-input');
    $this->view('admin.reports.exports.aed_pdf', ['entries' => $entries, 'company' => null, 'generatedAt' => now()])
        ->assertSee('>3.8</td>', false)->assertSee('13,157.89');
});

it('handles zero and negative converted USD totals for calculated rates', function () {
    $usd = Currency::create(['name' => 'Dollar', 'code' => 'USD']);
    $credit = ($this->ledger)(100);
    $debit = ($this->ledger)(100, 'debit');
    Transaction::withoutEvents(function () use ($credit, $debit, $usd) {
        $credit->update(['currency_id' => $usd->id, 'actual_amount' => 467]);
        $debit->update(['currency_id' => $usd->id]);
    });
    $this->post(route('admin.reports.aed.store'), $this->payload)->assertSessionHasNoErrors();
    $entries = AedReportEntry::with('account')->get();
    AedReportEntry::refreshLiveBalances($entries);
    expect($entries->first()->calculated_exchange_rate)->toBeNull();
    $this->get(route('admin.reports.aed.index'))->assertOk()->assertSee('—');
    Transaction::withoutEvents(fn () => $credit->update(['amount' => 0]));
    AedReportEntry::refreshLiveBalances($entries);
    expect($entries->first()->calculated_exchange_rate)->toBe(-3.67);
});
