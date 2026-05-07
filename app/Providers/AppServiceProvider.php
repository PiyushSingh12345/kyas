<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
