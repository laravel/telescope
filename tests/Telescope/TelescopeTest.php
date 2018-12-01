<?php

namespace Laravel\Telescope\Tests\Telescope;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\QueryWatcher;
use Laravel\Telescope\Contracts\EntriesRepository;

class TelescopeTest extends FeatureTestCase
{
    private $count = 0;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            QueryWatcher::class => [
                'enabled' => true,
                'slow' => 0.9,
            ],
        ]);
    }

    protected function tearDown()
    {
        Telescope::$afterRecordingHook = null;

        parent::tearDown();
    }

    /**
     * @test
     */
    public function run_after_recording_callback()
    {
        Telescope::afterRecording(function () {
            $this->count++;
        });

        $this->app->get('db')->table('telescope_entries')->count();

        $this->app->get('db')->table('telescope_entries')->count();

        $this->assertSame(2, $this->count);
    }

    /**
     * @test
     */
    public function after_recording_callback_can_store_and_flush()
    {
        Telescope::afterRecording(function (Telescope $telescope) {
            if (count($telescope::$entriesQueue) > 1) {
                $repository = $this->app->make(EntriesRepository::class);
                $telescope->store($repository);
            }
        });

        $this->app->get('db')->table('telescope_entries')->count();

        $this->assertCount(1, Telescope::$entriesQueue);

        $this->app->get('db')->table('telescope_entries')->count();

        $this->assertCount(0, Telescope::$entriesQueue);

        $this->app->get('db')->table('telescope_entries')->count();

        $this->assertCount(1, Telescope::$entriesQueue);
    }
}
