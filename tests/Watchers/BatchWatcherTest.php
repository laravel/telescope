<?php

namespace Laravel\Telescope\Tests\Watchers;

use Exception;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Bus\Batchable;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\JobWatcher;
use Laravel\Telescope\Watchers\BatchWatcher;

class BatchWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            JobWatcher::class => true,
            BatchWatcher::class => true,
        ]);

        $app->get('config')->set('queue.failed.database', 'testbench');

        $app->get('config')->set('logging.default', 'syslog');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createJobsTable();
    }

    public function test_job_dispatch_registers_entries()
    {
        $batch = $this->app->get(QueueingDispatcher::class)->batch([
            new BananaJob('First Banana'),
            new FailedBananaJob('Second Banana'),
        ])->onQueue('on-demand')->onConnection('database')->dispatch();

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--max-jobs' => 2,
            '--queue' => 'on-demand',
        ]);

        $entries = $this->loadTelescopeEntries()->all();
        
        $this->assertSame(3, count($entries));

        $this->assertSame(EntryType::JOB, $entries[0]->type);
        $this->assertSame('processed', $entries[0]->content['status']);
        $this->assertSame('database', $entries[0]->content['connection']);
        $this->assertSame($batch->id, $entries[0]->family_hash);
        $this->assertSame(BananaJob::class, $entries[0]->content['name']);
        $this->assertSame('on-demand', $entries[0]->content['queue']);
        $this->assertSame('First Banana', $entries[0]->content['data']['payload']);

        $this->assertSame(EntryType::JOB, $entries[1]->type);
        $this->assertSame('failed', $entries[1]->content['status']);
        $this->assertSame('database', $entries[1]->content['connection']);
        $this->assertSame($batch->id, $entries[1]->family_hash);
        $this->assertSame(FailedBananaJob::class, $entries[1]->content['name']);
        $this->assertSame('on-demand', $entries[1]->content['queue']);
        $this->assertSame('Second Banana', $entries[1]->content['data']['payload']);

        $this->assertSame(EntryType::BATCH, $entries[2]->type);
        $this->assertSame(2, $entries[2]->content['totalJobs']);
        $this->assertSame(1, $entries[2]->content['failedJobs']);
        $this->assertSame($batch->id, $entries[2]->content['id']);
        $this->assertSame('on-demand', $entries[2]->content['queue']);
    }

    private function createJobsTable(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function ($table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->text('failed_job_ids');
            $table->text('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }
}

class BananaJob implements ShouldQueue
{
    use Batchable;

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

class FailedBananaJob implements ShouldQueue
{
    use Batchable;

    public $tries = 1;

    private $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        throw new Exception($this->payload);
    }
}
