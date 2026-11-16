<?php

namespace App\Http\Middleware;

use App\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Routes of a Sarafi are not served on the platform's own address, and an expired Sarafi is read-only. */
class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenancy = app(Tenancy::class);
        if ($tenancy->enabled() && ! $tenancy->initialized()) {
            return redirect('/');
        }

        // An expired Sarafi can still sign in, read and export its books, but not change them.
        // (Checked here, inside the "web" group, because the answer uses the session.)
        $tenant = $tenancy->tenant();
        if ($tenant && $tenant->isReadOnly() && ! $request->isMethodSafe() && ! $request->is('login', 'logout', 'logout-ping', 'user/ping')) {
            $message = __('Your subscription has ended. The books are open for reading and export; renew to record new entries.');

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'error' => $message, 'read_only' => true], 423)
                : back()->with('error', $message);
        }

        return $next($request);
    }
}
