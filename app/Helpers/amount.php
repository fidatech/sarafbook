<?php

if (!function_exists('format_amount')) {

    function format_amount($amount, $decimals = 2)
    {
        if ($amount === null) return '';

        $setting = cache()->rememberForever('app_setting', function () {
            return \App\Models\Setting::first();
        });

        $amount = (float) $amount;

        if ($setting && $setting->round_amount) {
            return number_format(round($amount), 0);
        }

        return number_format($amount, $decimals);
    }
}
