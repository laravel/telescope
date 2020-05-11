<?php

namespace Laravel\Telescope\Tests\Telescope;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\QueryWatcher;

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

    protected function tearDown(): void
    {
        Telescope::$afterRecordingHook = null;

        parent::tearDown();
    }

    /**
     * @test
     */
    public function run_after_recording_callback()
    {
        Telescope::afterRecording(function (Telescope $telescope, IncomingEntry $entry) {
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
        Telescope::afterRecording(function (Telescope $telescope, IncomingEntry $entry) {
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

    /**
     * @test
     */
    public function run_after_store_callback()
    {
        $storedEntries = null;
        $storedBatchId = null;
        Telescope::afterStoring(function (array $entries, $batchId) use (&$storedEntries, &$storedBatchId) {
            $storedEntries = $entries;
            $storedBatchId = $batchId;

            $this->count += count($entries);
        });

        $this->app->get('db')->table('telescope_entries')->count();

        $this->app->get('db')->table('telescope_entries')->count();

        $this->assertSame(0, $this->count);

        $repository = $this->app->make(EntriesRepository::class);
        Telescope::store($repository);

        $this->assertSame(2, $this->count);
        $this->assertSame(2, count($storedEntries));
        $this->assertSame(36, strlen($storedBatchId));
        $this->assertInstanceOf(IncomingEntry::class, $storedEntries[0]);
    }

    /**
     * @test
     */
    public function dont_start_recording_when_dispatching_job_synchronously()
    {
        Telescope::stopRecording();

        $this->assertFalse(Telescope::isRecording());

        $this->app->get(Dispatcher::class)->dispatch(
            new MySyncJob('Awesome Laravel')
        );

        $this->assertFalse(Telescope::isRecording());
    }
}

class MySyncJob implements ShouldQueue
{
    public $connection = 'sync';

    private $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        //
    }
}
