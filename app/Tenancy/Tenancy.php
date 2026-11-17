<?php

namespace App\Tenancy;

use App\Models\Central\Tenant;
use App\Models\Central\TenantDomain;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Switches the application to one Sarafi: its database, cache, files and override layer.
 *
 * A web request is switched once, by the IdentifyTenant middleware, before the session starts.
 * Commands and queue jobs use run() so that the previous state comes back afterwards.
 */
class Tenancy
{
    protected ?Tenant $tenant = null;

    /** Values of the application before a tenant was selected, to go back to them. */
    protected array $original = [];

    /** Override folders that were already loaded in this process (routes and bindings load once). */
    protected array $loadedOverrides = [];

    public function __construct(protected Application $app) {}

    public function enabled(): bool
    {
        return (bool) config('tenancy.enabled');
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function initialized(): bool
    {
        return $this->tenant !== null;
    }

    public function isCentralDomain(string $host): bool
    {
        return in_array(strtolower($host), array_map('strtolower', config('tenancy.central_domains', [])), true);
    }

    /** The Sarafi an address belongs to. Kept for a minute in a cache that is never tenant-switched. */
    public function findByDomain(string $host): ?Tenant
    {
        $host = strtolower($host);
        $id = Cache::store('central')->remember('tenant-domain:'.$host, 60, function () use ($host) {
            return TenantDomain::query()->where('domain', $host)->value('tenant_id') ?? 0;
        });

        return $id ? Tenant::query()->with('plan')->find($id) : null;
    }

    public function forgetDomain(string $host): void
    {
        Cache::store('central')->forget('tenant-domain:'.strtolower($host));
    }

    public function initialize(Tenant $tenant): void
    {
        if ($this->tenant && $this->tenant->is($tenant)) {
            return;
        }
        if ($this->tenant) {
            $this->end();
        }

        $this->original = [
            'database.default' => config('database.default'),
            'cache.prefix' => config('cache.prefix'),
            'filesystems.disks.local.root' => config('filesystems.disks.local.root'),
            'filesystems.disks.public.root' => config('filesystems.disks.public.root'),
            'permission.cache.key' => config('permission.cache.key'),
            'session.files' => config('session.files'),
            'cache.stores.file.path' => config('cache.stores.file.path'),
            'services.wasender' => config('services.wasender'),
            'app.url' => config('app.url'),
        ];

        // 1. database
        config([
            'database.connections.tenant.database' => $tenant->database,
            'database.connections.tenant.username' => $tenant->db_username ?: config('database.connections.tenant.username'),
            'database.connections.tenant.password' => $tenant->db_username ? $tenant->db_password : config('database.connections.tenant.password'),
            'database.default' => 'tenant',
        ]);
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');

        // 2. cache: every key of this Sarafi gets its own prefix
        config([
            'cache.prefix' => $this->original['cache.prefix'].'t'.$tenant->id.'_',
            'permission.cache.key' => 'spatie.permission.cache.t'.$tenant->id,
        ]);
        $this->resetCache();

        // 2b. file sessions and the file cache of this Sarafi are kept in its own folder, so a
        //     session made at one Sarafi can never be read at another
        foreach (['sessions', 'cache'] as $dir) {
            if (! is_dir($tenant->storagePath('framework/'.$dir))) {
                @mkdir($tenant->storagePath('framework/'.$dir), 0755, true);
            }
        }
        config([
            'session.files' => $tenant->storagePath('framework/sessions'),
            'cache.stores.file.path' => $tenant->storagePath('framework/cache'),
        ]);
        $this->resetCache();

        // 3. files: private uploads and the "public" disk live in the tenant's own folder
        config([
            'filesystems.disks.local.root' => $tenant->storagePath('app/private'),
            'filesystems.disks.public.root' => $tenant->storagePath('app/public'),
        ]);
        Storage::forgetDisk(['local', 'public']);

        // 4. settings kept on the tenant record (for example its own WhatsApp key)
        foreach ((array) $tenant->settings as $key => $value) {
            if (is_string($key) && $value !== null && $value !== '') {
                config([$key => $value]);
            }
        }
        config(['tenancy.features' => $tenant->allFeatures()]);

        // 5. links made outside a web request (queue jobs, commands) point at the Sarafi's address
        if ($this->app->runningInConsole() && ($url = $tenant->url())) {
            config(['app.url' => $url]);
            URL::forceRootUrl($url);
        }

        $this->tenant = $tenant;

        // 6. the tenant's own layer of changes
        $this->loadOverrides($tenant);
    }

    public function end(): void
    {
        if (! $this->tenant) {
            return;
        }

        config($this->original);
        DB::setDefaultConnection($this->original['database.default']);
        DB::purge('tenant');
        $this->resetCache();
        Storage::forgetDisk(['local', 'public']);
        config(['tenancy.features' => []]);
        if ($this->app->runningInConsole()) {
            URL::forceRootUrl(null);
        }

        $this->tenant = null;
        $this->original = [];
    }

    /** Run something as a Sarafi and come back to where we were. */
    public function run(Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->tenant;
        $this->initialize($tenant);

        try {
            return $callback($tenant);
        } finally {
            $previous ? $this->initialize($previous) : $this->end();
        }
    }

    /** Run something on the central database even while a Sarafi is selected. */
    public function central(callable $callback): mixed
    {
        $previous = $this->tenant;
        $this->end();

        try {
            return $callback();
        } finally {
            if ($previous) {
                $this->initialize($previous);
            }
        }
    }

    protected function resetCache(): void
    {
        $cache = $this->app['cache'];
        $default = config('cache.default');
        $cache->forgetDriver($default);
        if ($default !== 'central') {
            $cache->purge($default);
        }
        // the cache.store singleton and the rate limiter hold the old store
        $this->app->forgetInstance('cache.store');
        $this->app->forgetInstance(\Illuminate\Cache\RateLimiter::class);

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class) && $this->app->resolved(\Spatie\Permission\PermissionRegistrar::class)) {
            $registrar = $this->app->make(\Spatie\Permission\PermissionRegistrar::class);
            $registrar->cacheKey = config('permission.cache.key');
            if (method_exists($registrar, 'initializeCache')) {
                $registrar->initializeCache();
            }
            if (method_exists($registrar, 'clearPermissionsCollection')) {
                $registrar->clearPermissionsCollection();
            }
        }
    }

    /**
     * tenants/<slug>/ may hold, all optional:
     *   views/        Blade files that take the place of the same path in resources/views
     *   lang/         translation files that take the place of single lines
     *   config.php    returns ['some.config.key' => value, ...]
     *   bindings.php  returns [Abstract::class => Concrete::class, ...] to swap a class for this Sarafi
     *   routes.php    extra routes, loaded inside the "web" + "tenant" middleware
     *   boot.php      any other set-up, run once
     *   migrations/   run only on this Sarafi's database by tenants:migrate
     */
    protected function loadOverrides(Tenant $tenant): void
    {
        $path = $tenant->overridesPath();
        if (! is_dir($path)) {
            return;
        }

        if (is_dir($path.'/views')) {
            $this->app['view']->getFinder()->prependLocation($path.'/views');
        }
        if (is_dir($path.'/lang')) {
            $loader = $this->app['translator']->getLoader();
            if (method_exists($loader, 'addPath')) {
                $loader->addPath($path.'/lang');
            }
            if (method_exists($loader, 'addJsonPath')) {
                $loader->addJsonPath($path.'/lang');
            }
        }
        if (is_file($path.'/config.php')) {
            config((array) require $path.'/config.php');
        }

        if (isset($this->loadedOverrides[$path])) {
            return;
        }
        $this->loadedOverrides[$path] = true;

        if (is_file($path.'/bindings.php')) {
            foreach ((array) require $path.'/bindings.php' as $abstract => $concrete) {
                $this->app->bind($abstract, $concrete);
            }
        }
        if (is_file($path.'/routes.php')) {
            Route::middleware(['web', 'tenant'])->group($path.'/routes.php');
            $this->app['router']->getRoutes()->refreshNameLookups();
            $this->app['router']->getRoutes()->refreshActionLookups();
        }
        if (is_file($path.'/boot.php')) {
            require $path.'/boot.php';
        }
    }
}
