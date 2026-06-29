<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Basic gates - allow customizing later
        Gate::define('manage-stock', function (Authenticatable $user) {
            // Default: allow all authenticated users. Adjust to your roles later.
            return true;
        });

        Gate::define('view-inventory-report', function (Authenticatable $user) {
            return true;
        });

        Gate::define('view-shrinkage-report', function (Authenticatable $user) {
            return true;
        });
    }
}
