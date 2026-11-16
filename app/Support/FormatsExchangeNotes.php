<?php

namespace App\Support;

use App\Models\Currency;

trait FormatsExchangeNotes
{
    private function formatExchangeNote(
        float $fromAmount,
        ?Currency $fromCurrency,
        float $toAmount,
        ?Currency $toCurrency,
        float $rate,
        string $date
    ): string {
        $fromCurrencyName = $fromCurrency?->name ?: $fromCurrency?->code;
        $toCurrencyName = $toCurrency?->name ?: $toCurrency?->code;

        return implode(' | ', [
            'تبادله اسعار',
            'از ' . $this->formatExchangeNoteNumber($fromAmount) . ' ' . $fromCurrencyName .
                ' به ' . $this->formatExchangeNoteNumber($toAmount) . ' ' . $toCurrencyName,
            'نرخ ' . number_format($rate, abs($rate) < 1 ? 4 : 2, '.', ''),
            'تاریخ ' . $date,
        ]);
    }

    private function formatExchangeNoteNumber(float $number): string
    {
        return number_format($number, 2, '.', ',');
    }
}
