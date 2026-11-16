<?php

use App\Models\Currency;
use App\Support\FormatsExchangeNotes;

function exchangeNote(
    float $fromAmount,
    Currency $fromCurrency,
    float $toAmount,
    Currency $toCurrency,
    float $rate,
    string $date
): string {
    $formatter = new class {
        use FormatsExchangeNotes;

        public function format(...$arguments): string
        {
            return $this->formatExchangeNote(...$arguments);
        }
    };

    return $formatter->format(
        $fromAmount,
        $fromCurrency,
        $toAmount,
        $toCurrency,
        $rate,
        $date
    );
}

it('formats an exchange note like a compact remittance note without profit or loss', function () {
    $usd = new Currency(['name' => 'دالر', 'code' => 'USD']);
    $afn = new Currency(['name' => 'افغانی', 'code' => 'AFN']);

    expect(exchangeNote(1000, $usd, 70000, $afn, 70, '2026-11-06'))
        ->toBe('تبادله اسعار | از 1,000.00 دالر به 70,000.00 افغانی | نرخ 70.00 | تاریخ 2026-11-06')
        ->not->toContain('مفاد')
        ->not->toContain('ضرر');
});

it('falls back to currency codes and formats amounts to two decimal places', function () {
    $usd = new Currency(['code' => 'USD']);
    $aed = new Currency(['code' => 'AED']);

    expect(exchangeNote(12.5, $usd, 45.875, $aed, 3.67, '2026-11-06'))
        ->toBe('تبادله اسعار | از 12.50 USD به 45.88 AED | نرخ 3.67 | تاریخ 2026-11-06');
});

it('uses comma separators for large exchange amounts', function () {
    $afn = new Currency(['name' => 'افغانی', 'code' => 'AFN']);
    $usd = new Currency(['name' => 'دالر', 'code' => 'USD']);

    expect(exchangeNote(100000, $afn, 1538.46153846, $usd, 0.02, '2026-11-06'))
        ->toBe('تبادله اسعار | از 100,000.00 افغانی به 1,538.46 دالر | نرخ 0.0200 | تاریخ 2026-11-06');
});
