<?php

namespace App\Providers;

use App\Models\AnalysisJob;
use App\Models\VideoAsset;
use App\Policies\AnalysisJobPolicy;
use App\Policies\VideoAssetPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(VideoAsset::class, VideoAssetPolicy::class);
        Gate::policy(AnalysisJob::class, AnalysisJobPolicy::class);
    }
}
