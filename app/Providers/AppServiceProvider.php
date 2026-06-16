<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Models\StatewiseAapAllocation;
use App\Observers\StatewiseAapAllocationObserver;
use App\Models\PdwiseAapAllocation;
use App\Observers\PdWiseAapAllocationObserver;
use App\Models\BudgetPhase;
use App\Observers\BudgetPhaseObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Keep DB session time zone consistent with application time zone (IST).
        // This prevents `CURRENT_TIMESTAMP` / `useCurrent()` columns from being stored in UTC.
        try {
            if (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
                DB::statement("SET time_zone = '+05:30'");
            }
        } catch (\Throwable $e) {
            // If DB isn't available during boot (e.g. artisan config:cache), skip.
        }

        RateLimiter::for('budget-heads', function (Request $request) {
            // Prefer per-user limits (authenticated) and fall back to client IP.
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(10)->by('budget-heads:' . $key);
        });

        Vite::prefetch(concurrency: 3);
        StatewiseAapAllocation::observe(StatewiseAapAllocationObserver::class);
        PdwiseAapAllocation::observe(PdWiseAapAllocationObserver::class);
        BudgetPhase::observe(BudgetPhaseObserver::class);
    }
}
