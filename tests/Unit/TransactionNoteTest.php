<?php

use App\Http\Controllers\Admin\TransactionsController;
use App\Models\Account;

function transactionNote(string $mode, string $type): string
{
    $account = new Account(['name' => 'احمد']);
    $trackAccount = new Account(['name' => 'محمود']);

    return (new ReflectionMethod(TransactionsController::class, 'transactionNote'))->invoke(
        new TransactionsController(),
        $mode,
        $type,
        100,
        'دالر',
        $account,
        $trackAccount
    );
}

it('uses the correct source and destination for tracked transactions', function () {
    expect(transactionNote('track', 'credit'))
        ->toBe('انتقال پول به احمد درک محمود | 100 دالر')
        ->and(transactionNote('track', 'debit'))
        ->toBe('انتقال پول به محمود درک احمد | 100 دالر');
});

it('uses distinct messages for untracked credit and debit transactions', function () {
    expect(transactionNote('no_track', 'credit'))
        ->toBe('آوردگی احمد | 100 دالر')
        ->and(transactionNote('no_track', 'debit'))
        ->toBe('بردگی احمد | 100 دالر');
});

it('uses distinct treasury messages for cash and account transactions', function () {
    expect(transactionNote('treasure', 'credit'))
        ->toBe('آوردگی احمد | 100 دالر')
        ->and(transactionNote('treasure', 'debit'))
        ->toBe('بردگی احمد | 100 دالر');
});
