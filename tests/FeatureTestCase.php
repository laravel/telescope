<?php

namespace Laravel\Telescope\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestResponse;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Assert as PHPUnit;

class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();

        TestResponse::macro('terminateTelescope', [$this, 'terminateTelescope']);

        TestResponse::macro('assertJsonExactFragment', function ($expected, $key) {
            $jsonResponse = $this->json();

            PHPUnit::assertEquals(
                $expected,
                $actualValue = data_get($jsonResponse, $key),
                "Failed asserting that [$actualValue] matches expected [$expected]." . PHP_EOL . PHP_EOL .
                json_encode($jsonResponse)
            );

            return $this;
        });

        Telescope::flushEntries();
    }

    protected function tearDown()
    {
        Telescope::flushEntries();

        parent::tearDown();
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