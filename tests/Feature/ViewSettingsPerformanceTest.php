<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

it('loads company settings once across page partials and refreshes for the next request', function () {
    $currency = \App\Models\Currency::create(['name' => 'Afghani', 'code' => 'AFN', 'is_active' => true]);
    $setting = Setting::create(['company_name' => 'Before', 'currency' => $currency->id]);
    app()->forgetScopedInstances();
    DB::enableQueryLog();
    DB::flushQueryLog();

    foreach (['layouts.admin.base', 'layouts.admin.menu', 'layouts.admin.navbar'] as $name) {
        $view = View::make($name);
        View::callComposer($view);
        expect($view->getData()['setting']->id)->toBe($setting->id);
    }

    $queries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"settings"'));
    expect($queries)->toHaveCount(1);

    $setting->update(['company_name' => 'After']);
    app()->forgetScopedInstances();
    $view = View::make('layouts.admin.base');
    View::callComposer($view);
    expect($view->getData()['setting']->company_name)->toBe('After');
    DB::disableQueryLog();
});
