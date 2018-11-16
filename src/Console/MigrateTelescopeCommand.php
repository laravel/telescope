<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Illuminate\Database\Migrations\Migrator;

class MigrateTelescopeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Telescope tables in a separate database without creating the app\'s tables.';

    public function handle(Migrator $migrator, Repository $config)
    {
        $connection = $config->get('telescope.storage.database.connection');

        $migrator->setConnection($connection);

        $migrator->setOutput($this->output);

        if (! $migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        $migrator->setOutput($this->output)->run([__DIR__.'/../Storage/migrations']);
    }
}
