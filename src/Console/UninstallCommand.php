<?php

namespace Laravel\Telescope\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'telescope:uninstall')]
class UninstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:uninstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall Telescope resources';

    public function handle()
    {
        $this->comment('Uninstalling Telescope resources...');
        $this->removeTelescopeFromBootstrap();

        $this->info('Telescope resources uninstalled successfully.');
    }

    protected function removeTelescopeFromBootstrap()
    {
        if (method_exists(ServiceProvider::class, 'removeProviderFromBootstrapFile') &&
            ServiceProvider::removeProviderFromBootstrapFile('TelescopeServiceProvider')) { // @phpstan-ignore-line
            return;
        }

        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (! Str::contains($appConfig, $namespace.'\\Providers\\TelescopeServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        $out = (new Collection(explode($eol, $appConfig)))
            ->reject(function ($line) {
                return Str::contains($line, 'TelescopeServiceProvider::class');
            })->implode($eol);

        file_put_contents(config_path('app.php'), $out);
    }
}
