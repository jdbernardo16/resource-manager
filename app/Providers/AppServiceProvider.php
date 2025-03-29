<?php

namespace App\Providers;

// Import Models
use App\Models\Project;
use App\Models\Resource;
use App\Models\ProjectAssignment;

// Import Observers
use App\Observers\ProjectObserver;
use App\Observers\ResourceObserver;
use App\Observers\ProjectAssignmentObserver;

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
        Project::observe(ProjectObserver::class);
        Resource::observe(ResourceObserver::class);
        ProjectAssignment::observe(ProjectAssignmentObserver::class);
    }
}
