<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        // 🔥 Get from session first
        $locale = Session::get('locale');

        if (!$locale) {
            // 🔥 fallback to DB (cached)
            $setting = Cache::rememberForever('app_setting', function () {
                return Setting::first();
            });

            $locale = $setting?->default_language ?? config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
