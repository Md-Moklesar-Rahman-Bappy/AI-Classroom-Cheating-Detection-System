<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\Gate::policy(\App\Models\VideoAsset::class, \App\Policies\VideoAssetPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\AnalysisJob::class, \App\Policies\AnalysisJobPolicy::class);
    }
}
