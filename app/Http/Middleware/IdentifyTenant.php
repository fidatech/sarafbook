<?php

namespace App\Http\Middleware;

use App\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First middleware of every request: the address decides which Sarafi this is, and the
 * application switches to its database before the session, the cache or a controller is used.
 */
class IdentifyTenant
{
    public function __construct(protected Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tenancy->enabled() || $this->tenancy->isCentralDomain($request->getHost())) {
            return $next($request);
        }

        $tenant = $this->tenancy->findByDomain($request->getHost());
        if (! $tenant) {
            return response()->view('errors.tenant', ['state' => 'unknown'], 404);
        }
        if ($tenant->isSuspended()) {
            return response()->view('errors.tenant', ['state' => 'suspended', 'tenant' => $tenant], 503);
        }

        $this->tenancy->initialize($tenant);

        return $next($request);
    }
}
