<?php

namespace Laravel\Telescope\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

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
        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, "App\Providers\TelescopeServiceProvider::class")) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "App\\Providers\EventServiceProvider::class,".PHP_EOL,
            "App\\Providers\EventServiceProvider::class,".PHP_EOL."        App\Providers\TelescopeServiceProvider::class,".PHP_EOL,
            $appConfig
        ));
    }
}
