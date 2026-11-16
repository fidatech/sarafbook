<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Plan;
use App\Models\Central\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /** Features a plan can switch on or off; the application asks with feature('name'). */
    public const FEATURES = [
        'whatsapp' => 'WhatsApp messages',
        'remittance_evidence' => 'Remittance payment with ID and photo',
        'exports' => 'Excel and PDF exports',
        'custom_domain' => 'Own domain',
        'backups' => 'Downloadable backups',
    ];

    public function index()
    {
        return view('platform.plans', [
            'plans' => Plan::query()->withCount('tenants')->orderBy('sort')->get(),
            'features' => self::FEATURES,
        ]);
    }

    public function store(Request $request)
    {
        $plan = Plan::query()->create($this->validated($request));
        PlatformAudit::record('plan_created', null, ['plan' => $plan->slug]);

        return back()->with('success', __('Saved.'));
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request, $plan));
        PlatformAudit::record('plan_updated', null, ['plan' => $plan->slug]);

        return back()->with('success', __('Saved.'));
    }

    protected function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/', Rule::unique('central.plans', 'slug')->ignore($plan?->id)],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
        ]);

        $data['features'] = collect(array_keys(self::FEATURES))->mapWithKeys(fn ($key) => [$key => ! empty($data['features'][$key])])->all();
        $data['setup_fee'] = $data['setup_fee'] ?? 0;
        $data['sort'] = $data['sort'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
