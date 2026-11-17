<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // "/" and the public website (routes/site.php).
            Route::middleware(['web', 'set_locale'])->group(base_path('routes/site.php'));

            // The platform panel: only on the platform's own address (CENTRAL_DOMAINS).
            Route::middleware(['web', 'central', 'set_locale'])
                ->prefix('platform')
                ->as('platform.')
                ->group(base_path('routes/platform.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The address selects the Sarafi before anything else runs.
        $middleware->prepend(\App\Http\Middleware\IdentifyTenant::class);
        // Pages the browser loads ahead of time: never-list, per-user limit, dropped after a save.
        $middleware->append(\App\Http\Middleware\PrefetchGuard::class);
        $middleware->append(\App\Http\Middleware\NormalizeNumericInputs::class);
        $middleware->append(\App\Http\Middleware\CacheHeaders::class);

        // Behind Nginx on the same server; set TRUSTED_PROXIES to its address if it is elsewhere.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'set_locale' => \App\Http\Middleware\SetLocale::class,
            'permission.feedback' => \App\Http\Middleware\CheckPermissionWithFeedback::class,
            'tenant' => \App\Http\Middleware\EnsureTenant::class,
            'central' => \App\Http\Middleware\EnsureCentral::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('platform', 'platform/*') ? '/platform/login' : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('platform', 'platform/*') ? '/platform' : '/admin/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // which page failed: method and path only, never the query or the form (logs can be shown in public CI output)
        $exceptions->context(fn () => app()->runningInConsole() ? [] : [
            'request' => request()->method().' '.request()->getHost().'/'.ltrim(request()->path(), '/'),
            'early' => request()->prefetch() ? 'prefetch' : null,
        ]);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // an expired form token sends the user back to the sign-in page instead of an error page
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                return redirect($request->is('platform', 'platform/*') ? '/platform/login' : '/login');
            }
        });
    })->create();
