<?php

namespace Laravel\Telescope\Console;

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

        $this->registerNovaServiceProvider();

        $this->info('Telescope scaffolding installed successfully.');
    }

    /**
     * Register the Telescope service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerNovaServiceProvider()
    {
        file_put_contents(config_path('app.php'), str_replace(
            "App\\Providers\EventServiceProvider::class,".PHP_EOL,
            "App\\Providers\EventServiceProvider::class,".PHP_EOL."        App\Providers\TelescopeServiceProvider::class,".PHP_EOL,
            file_get_contents(config_path('app.php'))
        ));
    }
}
