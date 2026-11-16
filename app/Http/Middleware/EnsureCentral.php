<?php

namespace App\Http\Middleware;

use App\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The platform panel exists only on the platform's own address, and only when tenancy is on. */
class EnsureCentral
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenancy = app(Tenancy::class);
        abort_unless($tenancy->enabled() && ! $tenancy->initialized(), 404);

        return $next($request);
    }
}
