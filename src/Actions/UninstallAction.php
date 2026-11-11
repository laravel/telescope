<?php

namespace Laravel\Telescope\Actions;

use Illuminate\Support\ServiceProvider;

class UninstallAction
{
    public function handle(): void
    {
        ServiceProvider::removeProviderFromBootstrapFile('TelescopeServiceProvider');
    }
}
