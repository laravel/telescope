<?php

namespace Laravel\Telescope\Tests\Watchers;

use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\JobWatcher;
use Throwable;

class JobWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            JobWatcher::class => true,
        ]);

        $app->get('config')->set('queue.failed.database', 'testbench');

        $app->get('config')->set('logging.default', 'syslog');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createJobsTable();
    }

    public function test_job_registers_entry()
    {
        $this->app->get(Dispatcher::class)->dispatch(new MyDatabaseJob('Awesome Laravel'));

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--queue' => 'on-demand',
        ])->run();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::JOB, $entry->type);
        $this->assertSame('processed', $entry->content['status']);
        $this->assertSame('database', $entry->content['connection']);
        $this->assertSame(MyDatabaseJob::class, $entry->content['name']);
        $this->assertSame('on-demand', $entry->content['queue']);
        $this->assertSame('Awesome Laravel', $entry->content['data']['payload']);
    }

    public function test_job_registers_entry_with_batchId_in_payload()
    {
        $this->app->get(Dispatcher::class)->dispatch(new MockedBatchableJob($batchId = (string) Str::orderedUuid()));

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
            '--queue' => 'on-demand',
        ])->run();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::JOB, $entry->type);
        $this->assertSame('processed', $entry->content['status']);
        $this->assertSame('database', $entry->content['connection']);
        $this->assertSame(MockedBatchableJob::class, $entry->content['name']);
        $this->assertSame('on-demand', $entry->content['queue']);
        $this->assertSame($batchId, $entry->content['data']['batchId']);
    }

    public function test_failed_jobs_register_entry()
    {
        $this->app->get(Dispatcher::class)->dispatch(
            new MyFailedDatabaseJob('I never watched Star Wars.')
        );

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
        ])->run();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::JOB, $entry->type);
        $this->assertSame('failed', $entry->content['status']);
        $this->assertSame('database', $entry->content['connection']);
        $this->assertSame(MyFailedDatabaseJob::class, $entry->content['name']);
        $this->assertSame('default', $entry->content['queue']);
        $this->assertSame('I never watched Star Wars.', $entry->content['data']['message']);
        $this->assertArrayHasKey('exception', $entry->content);

        $this->assertArrayNotHasKey('args', $entry->content['exception']['trace'][0]);
        $this->assertSame(MyFailedDatabaseJob::class, $entry->content['exception']['trace'][0]['class']);
        $this->assertSame('handle', $entry->content['exception']['trace'][0]['function']);
    }

    public function test_it_handles_pushed_jobs()
    {
        $queueExceptions = [];
        $this->app[ExceptionHandler::class]->reportable(function (Throwable $e) use (&$queueExceptions) {
            $queueExceptions[] = $e;
        });

        $this->app[QueueManager::class]
            ->connection('database')
            ->push(MyPushedJobClass::class, ['framework' => 'Laravel']);
        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
        ]);

        $entry = $this->loadTelescopeEntries()->first();
        $this->assertCount(1, $queueExceptions);
        $this->assertInstanceOf(PushedJobFailedException::class, $queueExceptions[0]);
        $this->assertSame(EntryType::JOB, $entry->type);
        $this->assertSame('failed', $entry->content['status']);
        $this->assertSame('database', $entry->content['connection']);
        $this->assertSame(MyPushedJobClass::class, $entry->content['name']);
        $this->assertSame('default', $entry->content['queue']);
        $this->assertSame(['framework' => 'Laravel'], $entry->content['data']);
    }

    private function createJobsTable(): void
    {
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->uuid('uuid');
                $table->bigIncrements('id');
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }
}

class MockedBatchableJob implements ShouldQueue
{
    public $connection = 'database';

    public $queue = 'on-demand';

    public $batchId;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle()
    {
        //
    }
}

class MyDatabaseJob implements ShouldQueue
{
    public $connection = 'database';

    public $queue = 'on-demand';

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

class MyFailedDatabaseJob implements ShouldQueue
{
    public $connection = 'database';

    public $tries = 1;

    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        throw new Exception($this->message);
    }
}

class MyPushedJobClass
{
    public $tries = 1;

    public function fire(Job $job, array $data)
    {
        throw new PushedJobFailedException();
    }
}

class PushedJobFailedException extends Exception
{
    //
}
