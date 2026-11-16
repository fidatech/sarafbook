<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Remittance;
use App\Support\FormatsRemittanceNotes;
use Tests\TestCase;

uses(TestCase::class);

function remittanceNote(
    string $direction,
    ?string $number = null,
    ?Account $trackAccount = null,
    ?string $trackNumber = null,
    bool $pending = false,
    bool $payment = false,
    ?Account $cashCounterpart = null,
    ?Account $remittanceDestination = null,
    string $mode = 'no_track'
): string {
    $formatter = new class {
        use FormatsRemittanceNotes;

        public function format(Remittance $remittance, ...$arguments): string
        {
            return $this->formatRemittanceNote($remittance, ...$arguments);
        }
    };

    $remittance = new Remittance([
        'hawala_number' => '25',
        'sender' => 'احمد',
        'receiver' => 'محمود',
        'amount' => 20000,
        'description' => 'تسلیم به گیرنده',
        'remittance_mode' => $mode,
    ]);
    $remittance->setRelation('currency', new Currency(['name' => 'افغانی', 'code' => 'AFN']));

    return $formatter->format(
        $remittance,
        $direction,
        $number,
        $trackAccount,
        $trackNumber,
        $pending,
        $payment,
        $cashCounterpart,
        $remittanceDestination
    );
}

function outgoingTrackedTransactionNote(string $transactionType, int $trackAccountSubCategoryId = 2): string
{
    $formatter = new class {
        use FormatsRemittanceNotes;

        public function format(Remittance $remittance, string $transactionType): string
        {
            return $this->formatOutgoingTrackedTransactionNote($remittance, $transactionType);
        }
    };

    $remittance = new Remittance([
        'hawala_number' => '25',
        'track_hawala_number' => '80',
        'sender' => 'احمد',
        'receiver' => 'محمود',
        'amount' => 20000,
        'remittance_mode' => 'track',
    ]);
    $remittance->setRelation('currency', new Currency(['name' => 'افغانی', 'code' => 'AFN']));
    $remittance->setRelation('toAccount', new Account(['name' => 'ارګون']));
    $remittance->setRelation('trackAccount', new Account([
        'name' => 'غزنی',
        'account_sub_category_id' => $trackAccountSubCategoryId,
    ]));

    return $formatter->format($remittance, $transactionType);
}

function incomingTrackedTransactionNote(string $transactionType, int $trackAccountSubCategoryId = 2): string
{
    $formatter = new class {
        use FormatsRemittanceNotes;

        public function format(Remittance $remittance, string $transactionType): string
        {
            return $this->formatIncomingTrackedTransactionNote($remittance, $transactionType);
        }
    };

    $remittance = new Remittance([
        'hawala_number' => '1',
        'track_hawala_number' => '1',
        'sender' => 'احمد',
        'receiver' => 'محمود',
        'remittance_mode' => 'track',
    ]);
    $remittance->setRelation('toAccount', new Account(['name' => 'دبی']));
    $remittance->setRelation('trackAccount', new Account([
        'name' => 'ارګون',
        'account_sub_category_id' => $trackAccountSubCategoryId,
    ]));

    return $formatter->format($remittance, $transactionType);
}

it('formats a standard remittance note with its financial details', function () {
    expect(remittanceNote('outgoing'))
        ->toBe(__('ui.outgoing') . ' | نمبر 25 | از احمد به محمود | 20000 افغانی | توضیحات: تسلیم به گیرنده');
});

it('formats a tracked remittance note with both references', function () {
    $trackAccount = new Account(['name' => 'کابل']);

    expect(remittanceNote('incoming', '80', $trackAccount, '25', mode: 'track'))
        ->toBe(__('ui.incoming') . ' | 80 | احمد - محمود | 25 کابل');
});

it('formats paid and pending states consistently', function () {
    expect(remittanceNote('incoming', pending: true))
        ->toContain('| ' . __('ui.pending') . ' |')
        ->and(remittanceNote('incoming', payment: true))
        ->toStartWith('پرداخت ' . __('ui.incoming') . ' |');
});

it('identifies treasure transactions as cash', function () {
    $counterpart = new Account(['name' => 'صراف کابل']);

    expect(remittanceNote('incoming', cashCounterpart: $counterpart, mode: 'treasure'))
        ->toBe(__('ui.incoming') . ' | 25 | احمد - محمود | نقد')
        ->and(remittanceNote('outgoing', mode: 'treasure'))
        ->toBe(__('ui.outgoing') . ' | 25 | احمد - محمود | نقد');
});

it('formats each side of an outgoing tracked remittance for its own account', function () {
    $dubai = new Account(['name' => 'دبی']);
    $argon = new Account(['name' => 'ارګون']);

    expect(remittanceNote('incoming', '25', $argon, '80', mode: 'track'))
        ->toBe(__('ui.incoming') . ' | 25 | احمد - محمود | 80 ارګون')
        ->and(remittanceNote('outgoing', '25', remittanceDestination: $dubai, mode: 'track'))
        ->toBe(__('ui.outgoing') . ' | 25 | احمد - محمود');
});

it('uses outgoing for credit and incoming for debit on outgoing tracked transactions', function () {
    expect(outgoingTrackedTransactionNote('credit'))
        ->toBe(__('ui.outgoing') . ' | 25 | احمد - محمود | 80 غزنی')
        ->and(outgoingTrackedTransactionNote('debit'))
        ->toBe(__('ui.incoming') . ' | 25 | احمد - محمود | 25 ارګون');
});

it('formats incoming tracked debit and credit rows with their own account context', function () {
    expect(incomingTrackedTransactionNote('debit'))
        ->toBe(__('ui.incoming') . ' | 1 | احمد - محمود | 1 ارګون')
        ->and(incomingTrackedTransactionNote('credit'))
        ->toBe(__('ui.outgoing') . ' | 1 | احمد - محمود');
});

it('hides track account and track hawala details for sub-category one on every transaction side', function () {
    $outgoingWithoutTrackDetails = __('ui.outgoing') . ' | 25 | احمد - محمود';
    $incomingWithoutTrackDetails = __('ui.incoming') . ' | 25 | احمد - محمود';

    expect(outgoingTrackedTransactionNote('credit', 1))
        ->toBe($outgoingWithoutTrackDetails)
        ->and(outgoingTrackedTransactionNote('debit', 1))
        ->toBe($incomingWithoutTrackDetails)
        ->and(incomingTrackedTransactionNote('credit', 1))
        ->toBe(__('ui.outgoing') . ' | 1 | احمد - محمود')
        ->and(incomingTrackedTransactionNote('debit', 1))
        ->toBe(__('ui.incoming') . ' | 1 | احمد - محمود');
});
