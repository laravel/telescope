<?php

namespace Laravel\Telescope;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TelescopeApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->authorization();
    }

    /**
     * Configure the Telescope authorization services.
     *
     * @return void
     */
    protected function authorization()
    {
        $this->gate();

        $guard = config('telescope.guard', null);

        Telescope::auth(function ($request) use ($guard) {
            return app()->environment('local') ||
                   Gate::check('viewTelescope', [$request->user($guard)]);
        });
    }

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
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
