<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeNumericInputs
{
    // Financial values only: never rewrite identifiers, contact details, or free text.
    private const FIELDS = [
        'amount', 'from_amount', 'to_amount', 'partner_amount', 'actual_amount',
        'payment_amount', 'commission_kabul_amount', 'commission_partner_amount',
        'rate', 'exchange_rate',
    ];

    public function handle(Request $request, Closure $next)
    {
        $normalize = function (array $values) use (&$normalize): array {
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $values[$key] = $normalize($value);
                } elseif (in_array($key, self::FIELDS, true) && is_string($value)) {
                    $value = strtr(trim($value), array_combine(
                        preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩٫٬،', -1, PREG_SPLIT_NO_EMPTY),
                        str_split('01234567890123456789.,,')
                    ));
                    // Invalid grouping is left intact so numeric validation rejects it.
                    if (preg_match('/^[+-]?\d{1,3}(?:,\d{3})+(?:\.\d*)?$/D', $value)) {
                        $value = str_replace(',', '', $value);
                    }
                    $values[$key] = $value === '' ? null : $value;
                }
            }
            return $values;
        };
        $request->query->replace($normalize($request->query->all()));
        $request->request->replace($normalize($request->request->all()));
        if ($request->isJson()) {
            $request->json()->replace($normalize($request->json()->all()));
        }
        return $next($request);
    }
}
