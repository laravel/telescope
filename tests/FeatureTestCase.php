<?php

namespace Laravel\Telescope\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestResponse;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeServiceProvider;
use Laravel\Telescope\Watchers\RequestWatcher;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        TestResponse::macro('terminateTelescope', [$this, 'terminateTelescope']);
    }

    protected function getPackageProviders($app)
    {
        return [
            TelescopeServiceProvider::class
        ];
    }

    protected function resolveApplicationCore($app)
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(function () {
            return 'self-testing';
        });
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('logging.default', 'errorlog');

        $config->set('database.default', 'testbench');

        $config->set('telescope.storage.database.connection', 'testbench');

        $config->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give('testbench');
    }

    public function terminateTelescope()
    {
        Telescope::store(app(EntriesRepository::class));
    }

    protected function prepareDatabase()
    {
        Telescope::withoutRecording(function () {
            $this->loadLaravelMigrations();

            $this->terminateTelescope();

            EntryModel::query()->truncate();
        });
    }
}