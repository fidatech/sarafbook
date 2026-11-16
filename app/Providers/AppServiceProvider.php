<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Http\Responses\LoginResponse;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->scoped('view.company-settings', fn () => (object) [
            'value' => \App\Models\Setting::first(),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('dirMargin', function () {
            return "<?php echo \$isRtl ? 'ms-2' : 'me-2'; ?>";
        });

        View::composer('*', function ($view) {
            $rtlLanguages = ['fa', 'ps'];
            $locale = app()->getLocale();
            $view->with('isRtl', in_array($locale, $rtlLanguages));
            $view->with('setting', app('view.company-settings')->value);
        });

        Transaction::observe(TransactionObserver::class);
    }
}
