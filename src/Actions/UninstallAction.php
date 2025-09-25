<?php

namespace Laravel\Telescope\Actions;

use Illuminate\Support\ServiceProvider;

class UninstallAction
{
    public function handle()
    {
        if (method_exists(ServiceProvider::class, 'removeProviderFromBootstrapFile') &&
            ServiceProvider::removeProviderFromBootstrapFile('TelescopeServiceProvider')) {
            return;
        }
    }
}
