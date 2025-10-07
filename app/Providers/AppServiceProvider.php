<?php

namespace App\Providers;

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
        Vite::prefetch(concurrency: 3);
        StatewiseAapAllocation::observe(StatewiseAapAllocationObserver::class);
        PdwiseAapAllocation::observe(PdWiseAapAllocationObserver::class);
        BudgetPhase::observe(BudgetPhaseObserver::class);
    }
}
