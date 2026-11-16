<?php

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Tester', 'email' => 'journal-receipt@example.test',
        'username' => 'journal-receipt', 'password' => 'password', 'account_type' => 'admin',
    ]);
    $category = AccountCategory::create(['name' => 'Accounts', 'code_prefix' => '#']);
    $subCategory = AccountSubCategory::create(['name' => 'Customer', 'account_category_id' => $category->id]);
    $this->account = Account::create([
        'name' => 'حاجی محمود', 'code' => '#JR1', 'account_sub_category_id' => $subCategory->id,
    ]);
    $this->currency = Currency::create(['name' => 'افغانی', 'code' => 'AFN', 'is_active' => true]);
});

function journalReceiptTransaction(array $values): Transaction
{
    $currencyId = test()->currency->id;

    return Transaction::create(array_merge([
        'table_name' => 'Journal', 'account_id' => test()->account->id,
        'currency_id' => $currencyId, 'payment_currency_id' => $currencyId,
        'actual_currency_id' => $currencyId, 'amount' => 0, 'payment_amount' => 0,
        'actual_amount' => 0, 'transaction_type' => 'credit', 'created_by' => test()->user->id,
        'status' => 'active', 'is_visible' => true,
    ], $values));
}

it('prints credit and debit journal receipts with the balance immediately after each entry', function () {
    Carbon::setTestNow('2026-11-11 15:30:00');

    journalReceiptTransaction(['amount' => 25, 'transaction_type' => 'debit', 'created_at' => '2026-11-11 09:00:00']);
    $credit = journalReceiptTransaction(['amount' => 100, 'note' => 'دریافت نقدی', 'created_at' => '2026-11-11 10:00:00']);
    journalReceiptTransaction(['amount' => 50, 'transaction_type' => 'debit', 'created_at' => '2026-11-11 11:00:00']);

    $this->withSession(['locale' => 'fa'])->actingAs($this->user)
        ->get(route('admin.transactions.print', $credit))
        ->assertOk()
        ->assertSee('رسید دریافت وجه')
        ->assertSee('مبلغ زیر به حساب شما واریز گردید')
        ->assertSee('حاجی محمود')
        ->assertSee('دریافت نقدی')
        ->assertSee('75 افغانی')
        ->assertSee('size:80mm auto', false);

    $debit = journalReceiptTransaction(['amount' => 20, 'transaction_type' => 'debit', 'created_at' => '2026-11-11 12:00:00']);

    $this->withSession(['locale' => 'fa'])->actingAs($this->user)
        ->get(route('admin.transactions.print', $debit))
        ->assertOk()
        ->assertSee('رسید پرداخت وجه')
        ->assertSee('مبلغ زیر از حساب شما برداشت گردید')
        ->assertSee('5 افغانی');

    Carbon::setTestNow();
});

it('does not print a transaction that was not created by Journal', function () {
    $transaction = journalReceiptTransaction(['table_name' => 'remittances', 'amount' => 100]);

    $this->actingAs($this->user)
        ->get(route('admin.transactions.print', $transaction))
        ->assertNotFound();
});
