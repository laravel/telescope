<?php

namespace Laravel\Telescope\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Queue;
use Illuminate\Testing\TestResponse;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

#[WithConfig('logging.default', 'errorlog')]
#[WithConfig('database.default', 'testbench')]
#[WithConfig('telescope.storage.database.connection', 'testbench')]
#[WithConfig('queue.batching.database', 'testbench')]
#[WithConfig('database.connections.testbench', [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => ''
])]
class FeatureTestCase extends TestCase
{
    use WithWorkbench, RefreshDatabase, WithLaravelMigrations;

    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('terminateTelescope', [$this, 'terminateTelescope']);

        Telescope::flushEntries();
        Telescope::$afterStoringHooks = [];
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        Telescope::flushEntries();
        Telescope::$afterStoringHooks = [];

        Queue::createPayloadUsing(null);

        parent::tearDown();
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function getPackageProviders($app)
    {
        return [
            TelescopeServiceProvider::class,
        ];
    }

    /** {@inheritdoc} */
    #[\Override]
    public function ignorePackageDiscoveriesFrom()
    {
        return ['*', 'spatie/laravel-ray'];
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function resolveApplicationCore($app)
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(function () {
            return 'self-testing';
        });
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $app->when(DatabaseEntriesRepository::class)
            ->needs('$connection')
            ->give('testbench');
    }

    protected function loadTelescopeEntries()
    {
        $this->terminateTelescope();

        return EntryModel::all();
    }

    public function terminateTelescope()
    {
        Telescope::store(app(EntriesRepository::class));
    }
}
