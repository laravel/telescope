<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\TelescopeApplicationServiceProvider as ServiceProvider;

class TelescopeServiceProvider extends ServiceProvider
{
    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewTelescope', function ($user) {
            return true;
        });
    }
}
