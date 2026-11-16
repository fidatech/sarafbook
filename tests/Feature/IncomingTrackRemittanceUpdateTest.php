<?php

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Remittance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('saves the submitted destination and forces incoming track sender to account one', function () {
    $this->actingAs(User::create([
        'name' => 'Tester', 'email' => 'track@example.test', 'username' => 'track-tester',
        'password' => 'password', 'account_type' => 'admin',
    ]));
    $category = AccountCategory::create(['name' => 'Accounts', 'code_prefix' => '#']);
    foreach (['Customer', 'Saraf', 'Cash'] as $name) {
        AccountSubCategory::create(['name' => $name, 'account_category_id' => $category->id]);
    }
    foreach ([3, 2, 2] as $index => $subcategory) {
        Account::create(['name' => 'Account '.$index, 'code' => '#'.$index, 'account_sub_category_id' => $subcategory]);
    }
    Currency::create(['name' => 'Dollar', 'code' => 'USD', 'is_active' => true]);
    $data = [
        'date' => '2026-09-08', 'type' => 'incoming', 'remittance_mode' => 'track',
        'from_account_id' => 1, 'to_account_id' => 2, 'track_account_id' => 3,
        'currency_id' => 1, 'amount' => 100, 'partner_currency_id' => 1,
        'partner_amount' => 100, 'actual_currency_id' => 1, 'actual_amount' => 100,
        'commission_mode' => 'none', 'status' => 'paid', 'created_by' => auth()->id(),
        'linked_id' => (string) Str::uuid(),
    ];
    $remittance = Remittance::create($data);
    $formatted = array_replace($data, [
        'amount' => '12,345', 'partner_amount' => '12,340.50', 'actual_amount' => '۱۲٬۳۰۰٫۲۵',
    ]);
    $this->postJson(route('admin.modal-remittances.store'), $formatted)->assertSuccessful();
    $created = Remittance::latest('id')->first();
    expect((float) $created->amount)->toBe(12345.0)
        ->and((float) $created->partner_amount)->toBe(12340.5)
        ->and((float) $created->actual_amount)->toBe(12300.25);
    $data = $formatted;
    $data['from_account_id'] = 3;
    $data['to_account_id'] = 3;
    $data['track_account_id'] = 2;
    $this->putJson(route('admin.modal-remittances.update', $remittance), $data)->assertOk();
    $remittance->refresh();
    expect((int) $remittance->from_account_id)->toBe(1)
        ->and((int) $remittance->to_account_id)->toBe(3)
        ->and((int) $remittance->track_account_id)->toBe(2);
    expect((float) $remittance->amount)->toBe(12345.0)
        ->and((float) $remittance->partner_amount)->toBe(12340.5)
        ->and((float) $remittance->actual_amount)->toBe(12300.25);
    $this->putJson(route('admin.modal-remittances.update', $remittance), array_replace($data, ['partner_amount' => '12,34']))
        ->assertUnprocessable()->assertJsonValidationErrors('partner_amount');
    expect(Transaction::where('linked_id', $remittance->linked_id)->where('account_id', 3)->exists())->toBeTrue();
    $data['to_account_id'] = null;
    $this->putJson(route('admin.modal-remittances.update', $remittance), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('to_account_id');
});
