<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Remittance;

trait FormatsRemittanceNotes
{
    private function formatOutgoingTrackedTransactionNote(
        Remittance $remittance,
        string $transactionType
    ): string {
        return match ($transactionType) {
            'credit' => $this->formatRemittanceNote(
                $remittance,
                'outgoing',
                $remittance->hawala_number,
                $remittance->trackAccount,
                $remittance->track_hawala_number
            ),
            'debit' => $this->formatRemittanceNote(
                $remittance,
                'incoming',
                $remittance->hawala_number,
                $remittance->toAccount,
                $remittance->hawala_number
            ),
            default => throw new \InvalidArgumentException(
                "Unsupported remittance transaction type: {$transactionType}"
            ),
        };
    }

    private function formatIncomingTrackedTransactionNote(
        Remittance $remittance,
        string $transactionType
    ): string {
        return match ($transactionType) {
            'credit' => $this->formatRemittanceNote(
                $remittance,
                'outgoing',
                $remittance->track_hawala_number
            ),
            'debit' => $this->formatRemittanceNote(
                $remittance,
                'incoming',
                $remittance->track_hawala_number,
                $remittance->trackAccount,
                $remittance->hawala_number
            ),
            default => throw new \InvalidArgumentException(
                "Unsupported remittance transaction type: {$transactionType}"
            ),
        };
    }

    private function formatRemittanceNote(
        Remittance $remittance,
        string $direction,
        ?string $hawalaNumber = null,
        ?Account $trackAccount = null,
        ?string $trackHawalaNumber = null,
        bool $pending = false,
        bool $payment = false,
        ?Account $cashCounterpart = null,
        ?Account $remittanceDestination = null
    ): string {
        $type = $direction === 'incoming' ? __('ui.incoming') : __('ui.outgoing');
        $prefix = $payment ? "پرداخت {$type}" : $type;
        $number = $hawalaNumber ?: $remittance->hawala_number;

        if (!in_array($remittance->remittance_mode, ['track', 'treasure'], true)) {
            $currency = $remittance->currency?->name ?: $remittance->currency?->code;
            $amount = $this->formatRemittanceNoteAmount($remittance->amount);
            $parts = [
                $prefix,
                "نمبر {$number}",
                "از {$remittance->sender} به {$remittance->receiver}",
                trim("{$amount} {$currency}"),
            ];

            if ($cashCounterpart) {
                $parts[] = "حساب {$cashCounterpart->name}";
            }

            if ($pending) {
                $parts[] = __('ui.pending');
            }

            $description = trim((string) $remittance->description);
            if ($description !== '') {
                $parts[] = "توضیحات: {$description}";
            }

            return implode(' | ', $parts);
        }

        $parts = [
            $prefix,
            $number,
            "{$remittance->sender} - {$remittance->receiver}",
        ];

        if ($this->shouldShowRemittanceTrackDetails($remittance) && $trackAccount && $trackHawalaNumber) {
            $parts[] = "{$trackHawalaNumber} {$trackAccount->name}";
        }

        if ($remittance->remittance_mode === 'treasure') {
            $parts[] = 'نقد';
        }

        return implode(' | ', $parts);
    }

    private function shouldShowRemittanceTrackDetails(Remittance $remittance): bool
    {
        return (int) $remittance->trackAccount?->account_sub_category_id !== 1;
    }

    private function formatRemittanceNoteAmount($amount): string
    {
        return rtrim(rtrim(number_format((float) $amount, 2, '.', ''), '0'), '.');
    }
}
