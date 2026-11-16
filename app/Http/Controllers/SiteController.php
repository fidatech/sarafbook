<?php

namespace App\Http\Controllers;

use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use App\Models\Central\TenantDomain;
use App\Tenancy\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** The public website on the platform's own address, and "/" of a Sarafi. */
class SiteController extends Controller
{
    public function home(Tenancy $tenancy)
    {
        // a Sarafi's address (or a single-Sarafi installation): straight into the system
        if (! $tenancy->enabled() || $tenancy->initialized()) {
            return auth()->check() ? redirect()->route('admin.dashboard') : redirect()->route('login');
        }

        $plans = collect();
        try {
            $plans = Plan::query()->where('is_active', true)->orderBy('sort')->orderBy('price_yearly')->get();
        } catch (\Throwable $e) {
            report($e);
        }

        return view('site.home', ['plans' => $plans]);
    }

    /** "Sign in to your Sarafi": the short name or the address leads to that Sarafi's own sign-in page. */
    public function find(Request $request)
    {
        $data = $request->validate(['sarafi' => ['required', 'string', 'max:191']]);

        $value = Str::of($data['sarafi'])->lower()->trim()->replaceMatches('#^https?://#', '')->before('/')->before(':')->toString();
        $base = '.'.strtolower((string) config('tenancy.base_domain'));

        $tenant = null;
        if ($value !== '' && preg_match('/^[a-z0-9.-]+$/', $value)) {
            if (str_contains($value, '.')) {
                $tenant = TenantDomain::query()->where('domain', $value)->first()?->tenant;
                if (! $tenant && str_ends_with($value, $base)) {
                    $value = substr($value, 0, -strlen($base));
                }
            }
            $tenant ??= Tenant::query()->where('slug', $value)->first();
        }

        if (! $tenant || $tenant->isSuspended() || ! $tenant->url()) {
            return redirect()->to(route('site.home').'#signin')
                ->withInput()
                ->with('find_error', __('No Sarafi was found with this name. Check the spelling, or ask us for your address.'));
        }

        return redirect()->away($tenant->url('login'));
    }
}
