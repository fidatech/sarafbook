<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Tenancy\Tenancy;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        // the user's own choice first
        $locale = Session::get('locale');

        if (! $locale) {
            $tenancy = app(Tenancy::class);
            if (! $tenancy->enabled() || $tenancy->initialized()) {
                // then the default language of the Sarafi (the cache is separate for each Sarafi)
                $setting = Cache::rememberForever('app_setting', function () {
                    return Setting::first();
                });
                $locale = $setting?->default_language;
            }
        }

        if (! array_key_exists((string) $locale, config('app.locales', []))) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
