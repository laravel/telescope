<?php

namespace Laravel\Telescope\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'telescope:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Telescope resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Telescope Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'telescope-provider']);

        $this->comment('Publishing Telescope Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'telescope-assets']);

        $this->comment('Publishing Telescope Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'telescope-config']);

        $this->comment('Publishing Telescope Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'telescope-migrations']);

        $this->registerTelescopeServiceProvider();

        $this->info('Telescope scaffolding installed successfully.');
    }

    /**
     * Register the Telescope service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerTelescopeServiceProvider()
    {
        if (method_exists(ServiceProvider::class, 'addProviderToBootstrapFile') &&
            ServiceProvider::addProviderToBootstrapFile(\App\Providers\TelescopeServiceProvider::class)) { // @phpstan-ignore-line
            return;
        }

        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\TelescopeServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol,
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol."        {$namespace}\Providers\TelescopeServiceProvider::class,".$eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/TelescopeServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/TelescopeServiceProvider.php'))
        ));
    }
}
