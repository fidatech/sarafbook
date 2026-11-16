<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\PlatformAudit;
use App\Models\Central\SubscriptionPayment;
use App\Models\Central\Tenant;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $counts = Tenant::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $soon = now()->addDays(30);

        return view('platform.dashboard', [
            'total' => (int) $counts->sum(),
            'active' => (int) ($counts['active'] ?? 0),
            'trial' => (int) ($counts['trial'] ?? 0),
            'suspended' => (int) ($counts['suspended'] ?? 0),
            'incomeYear' => SubscriptionPayment::query()->whereYear('paid_at', now()->year)->selectRaw('currency, sum(amount) as total')->groupBy('currency')->pluck('total', 'currency'),
            'expiring' => Tenant::query()->with('domains', 'plan')->where('status', '!=', 'suspended')
                ->where(fn ($q) => $q->whereBetween('expires_at', [now()->subDays(60), $soon])
                    ->orWhere(fn ($q) => $q->where('status', 'trial')->whereBetween('trial_ends_at', [now()->subDays(60), $soon])))
                ->orderByRaw('coalesce(expires_at, trial_ends_at)')->limit(15)->get(),
            'recent' => Tenant::query()->with('domains', 'plan')->latest()->limit(8)->get(),
        ]);
    }

    public function audit()
    {
        return view('platform.audit', [
            'entries' => PlatformAudit::query()->with('user', 'tenant')->latest('id')->paginate(50),
        ]);
    }
}
