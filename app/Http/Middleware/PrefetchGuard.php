<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The server's side of loading pages early.
 *
 * A request the browser makes ahead of time carries "Sec-Purpose: prefetch". Such a request is
 * refused for any address on the never-list (so a page that changes something can never run
 * ahead), and limited per user and minute (so early loading never slows the server). A refused
 * early request costs nothing: the browser loads the page normally when it is opened.
 *
 * After anything is saved (any request that is not GET or HEAD), the answer tells the browser
 * to drop every page it loaded early, so the next page opened is always fresh.
 */
class PrefetchGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (self::isEarly($request)) {
            if (! config('prefetch.enabled') || self::isNever('/'.ltrim($request->path(), '/'))) {
                return response('', 403, ['Cache-Control' => 'no-store']);
            }
            $key = 'prefetch:'.$request->getHost().':'.$request->ip();
            if (RateLimiter::tooManyAttempts($key, max(1, (int) config('prefetch.per_minute', 40)))) {
                return response('', 429, ['Cache-Control' => 'no-store', 'Retry-After' => '60']);
            }
            RateLimiter::hit($key, 60);
        }

        $response = $next($request);

        if (! $request->isMethodSafe()) {
            $response->headers->set('Clear-Site-Data', '"prefetchCache", "prerenderCache"');
        }

        return $response;
    }

    public static function isEarly(Request $request): bool
    {
        $purpose = strtolower((string) ($request->headers->get('Sec-Purpose') ?? $request->headers->get('Purpose') ?? $request->headers->get('X-Moz')));

        return str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender');
    }

    public static function isNever(string $path): bool
    {
        foreach ((array) config('prefetch.never', []) as $pattern) {
            if (Str::is($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
