<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Plan;
use App\Models\Central\PlatformAudit;
use App\Models\Central\Tenant;
use App\Models\User;
use App\Tenancy\Tenancy;
use App\Tenancy\TenantProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = $request->query('status');

        $tenants = Tenant::query()->with('domains', 'plan')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhereHas('domains', fn ($d) => $d->where('domain', 'like', "%{$search}%"))))
            ->when(in_array($status, ['trial', 'active', 'suspended'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('platform.tenants', [
            'tenants' => $tenants,
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort')->get(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function store(Request $request, TenantProvisioner $provisioner)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', Rule::unique('central.tenants', 'slug')],
            'domain' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', Rule::unique('central.tenant_domains', 'domain')],
            'plan_id' => ['nullable', Rule::exists('central.plans', 'id')],
            'status' => ['required', Rule::in(['trial', 'active'])],
            'expires_at' => ['nullable', 'date'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'admin_username' => ['required', 'string', 'min:4', 'max:40'],
            'locale' => ['required', Rule::in(array_keys(config('app.locales')))],
            'existing_database' => ['nullable', 'boolean'],
        ]);

        try {
            [$tenant, $username, $password] = $provisioner->create(array_filter($data, fn ($value) => $value !== null && $value !== ''));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        PlatformAudit::record('tenant_created', $tenant, ['database' => $tenant->database]);

        // the password is shown once, on the next page, and is not kept anywhere by the platform
        return redirect()->route('platform.tenants.show', $tenant)
            ->with('success', __('The Sarafi was created.'))
            ->with('first_login', $password === '' ? null : ['username' => $username, 'password' => $password]);
    }

    public function show(Tenant $tenant, Tenancy $tenancy)
    {
        $tenant->load('domains', 'plan', 'payments.plan', 'payments.recorder');

        // a few figures read from the Sarafi's own database; the page still opens if it cannot be reached
        $stats = null;
        try {
            $stats = $tenancy->run($tenant, fn () => [
                'users' => User::query()->count(),
                'accounts' => DB::table('accounts')->count(),
                'transactions' => DB::table('transactions')->count(),
                'last_entry' => DB::table('transactions')->max('created_at'),
                'size_mb' => $this->databaseSize($tenant->database),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return view('platform.tenant', [
            'tenant' => $tenant,
            'stats' => $stats,
            'plans' => Plan::query()->orderBy('sort')->get(),
            'hasOverrides' => is_dir($tenant->overridesPath()),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'plan_id' => ['nullable', Rule::exists('central.plans', 'id')],
            'expires_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'release' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'in:0,1'],
            'wasender_key' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = (array) $tenant->settings;
        if ($request->filled('wasender_key')) {
            $settings['services.wasender.key'] = $data['wasender_key'];
        } elseif ($request->boolean('wasender_clear')) {
            unset($settings['services.wasender.key']);
        }

        $tenant->update([
            'name' => $data['name'],
            'plan_id' => $data['plan_id'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'trial_ends_at' => $data['trial_ends_at'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'release' => $data['release'],
            'notes' => $data['notes'] ?? null,
            'features' => collect($data['features'] ?? [])->reject(fn ($v) => $v === null || $v === '')->map(fn ($v) => (bool) $v)->all() ?: null,
            'settings' => $settings ?: null,
        ]);
        PlatformAudit::record('tenant_updated', $tenant, ['changed' => array_keys($tenant->getChanges())]);

        return back()->with('success', __('Saved.'));
    }

    public function status(Request $request, Tenant $tenant)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['trial', 'active', 'suspended'])]]);
        $tenant->update(['status' => $data['status']]);
        PlatformAudit::record('tenant_status', $tenant, $data);

        return back()->with('success', __('Saved.'));
    }

    /** A renewal: record the money and move the end date. */
    public function payment(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', Rule::in(['cash', 'bank', 'hesabpay', 'hawala', 'other'])],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::connection('central')->transaction(function () use ($tenant, $data) {
            $tenant->payments()->create($data + [
                'plan_id' => $tenant->plan_id,
                'period_start' => $tenant->expires_at && $tenant->expires_at->isFuture() ? $tenant->expires_at->toDateString() : now()->toDateString(),
                'recorded_by' => auth('platform')->id(),
            ]);
            $tenant->update(['expires_at' => $data['period_end'], 'status' => 'active', 'trial_ends_at' => null]);
        });
        PlatformAudit::record('payment_recorded', $tenant, ['amount' => $data['amount'], 'currency' => $data['currency'], 'until' => $data['period_end']]);

        return back()->with('success', __('The payment was recorded and the subscription extended.'));
    }

    public function domain(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', Rule::unique('central.tenant_domains', 'domain')],
            'is_primary' => ['nullable', 'boolean'],
        ]);
        if (in_array(strtolower($data['domain']), config('tenancy.central_domains'), true)) {
            return back()->with('error', __('This address belongs to the platform.'));
        }

        if (! empty($data['is_primary'])) {
            $tenant->domains()->update(['is_primary' => false]);
        }
        $tenant->domains()->create(['domain' => $data['domain'], 'is_primary' => ! empty($data['is_primary'])]);
        PlatformAudit::record('domain_added', $tenant, ['domain' => $data['domain']]);

        return back()->with('success', __('Saved.'));
    }

    public function migrate(Tenant $tenant, TenantProvisioner $provisioner)
    {
        try {
            $output = trim($provisioner->migrate($tenant));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        PlatformAudit::record('tenant_migrated', $tenant);

        return back()->with('success', str_contains($output, 'Nothing to migrate') ? __('The database is up to date.') : __('The database was updated.'));
    }

    /** Open the Sarafi as its administrator for support. Every use is written to the audit log. */
    public function enter(Tenant $tenant)
    {
        $tenant->load('domains');
        $root = $tenant->url();
        abort_unless($root && ! $tenant->isSuspended(), 404);

        PlatformAudit::record('entered_tenant', $tenant);

        URL::forceRootUrl($root);
        try {
            $url = URL::temporarySignedRoute('tenant.enter', now()->addMinutes(2), [
                'tenant' => $tenant->id,
                'by' => auth('platform')->user()->email,
            ]);
        } finally {
            URL::forceRootUrl(null);
        }

        return redirect()->away($url);
    }

    protected function databaseSize(string $database): ?float
    {
        try {
            $bytes = DB::connection('central')->table('information_schema.tables')
                ->where('table_schema', $database)->selectRaw('sum(data_length + index_length) as bytes')->value('bytes');

            return $bytes === null ? null : round($bytes / 1048576, 1);
        } catch (\Throwable) {
            return null;
        }
    }
}
