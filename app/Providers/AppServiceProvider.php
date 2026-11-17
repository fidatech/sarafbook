<?php

namespace App\Providers;

use App\Models\Central\Tenant;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use App\Tenancy\Tenancy;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Tenancy::class);

        // pages loaded ahead of time read the session but never write it (docs/SPEED.md)
        $this->app->singleton(\Illuminate\Session\Middleware\StartSession::class, fn ($app) => new \App\Http\Middleware\StartSession(
            $app->make(\Illuminate\Session\SessionManager::class),
            fn () => $app->make(\Illuminate\Contracts\Cache\Factory::class),
        ));

        // Company settings of the Sarafi, read once for a request.
        $this->app->scoped('view.company-settings', fn () => (object) [
            'value' => $this->inTenantContext() ? \App\Models\Setting::first() : null,
        ]);

        if (config('tenancy.enabled')) {
            // Until a Sarafi is selected the application works on the central database; the queue
            // tables are shared there, and each job carries the Sarafi it belongs to.
            config([
                'database.default' => 'central',
                'queue.connections.database.connection' => 'central',
                'queue.failed.database' => 'central',
                'queue.batching.database' => 'central',
            ]);
        }
    }

    public function boot(): void
    {
        // `php artisan migrate` works on the platform tables when tenancy is on (tenant databases
        // are migrated with tenants:migrate), and on the Sarafi tables when it is off.
        $this->loadMigrationsFrom(database_path(config('tenancy.enabled') ? 'migrations/central' : 'migrations/tenant'));

        Blade::directive('dirMargin', function () {
            return "<?php echo \$isRtl ? 'ms-2' : 'me-2'; ?>";
        });

        View::composer('*', function ($view) {
            $view->with('isRtl', in_array(app()->getLocale(), ['fa', 'ps'], true));
            $view->with('setting', app('view.company-settings')->value);
        });

        Transaction::observe(TransactionObserver::class);

        $this->makeQueueTenantAware();
    }

    protected function inTenantContext(): bool
    {
        $tenancy = $this->app->make(Tenancy::class);

        return ! $tenancy->enabled() || $tenancy->initialized();
    }

    /** A queued job remembers its Sarafi and runs on that Sarafi's database. */
    protected function makeQueueTenantAware(): void
    {
        if (! config('tenancy.enabled')) {
            return;
        }

        Queue::createPayloadUsing(function () {
            $tenant = app(Tenancy::class)->tenant();

            return $tenant ? ['tenant_id' => $tenant->id] : [];
        });

        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            $tenancy = app(Tenancy::class);
            $id = $event->job->payload()['tenant_id'] ?? null;
            if (! $id) {
                $tenancy->end();

                return;
            }
            $tenant = $tenancy->central(fn () => Tenant::query()->with('domains', 'plan')->find($id));
            if (! $tenant) {
                throw new \RuntimeException("The Sarafi of this job (#{$id}) no longer exists.");
            }
            $tenancy->initialize($tenant);
        });

        // sync jobs run inside a web request and must leave its Sarafi selected
        $end = function ($event) {
            if ($event->connectionName !== 'sync') {
                app(Tenancy::class)->end();
            }
        };
        Event::listen(JobProcessed::class, $end);
        Event::listen(JobFailed::class, $end);
        Event::listen(JobExceptionOccurred::class, $end);
    }
}
