<?php

namespace Laravel\Telescope\Tests\Watchers;

use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\JobWatcher;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Throwable;

#[WithMigration('queue')]
#[WithConfig('queue.failed.database', 'testbench')]
#[WithConfig('logging.default', 'syslog')]
#[WithConfig('telescope.watchers', [
    JobWatcher::class => true,
], defer: false)]
class JobWatcherTest extends FeatureTestCase
{
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

    public function test_processed_job_clears_stale_failure_state_left_by_a_duplicate_reservation()
    {
        // A long-running job whose runtime exceeds the queue's retry_after can be
        // reserved twice: a second worker fails it with MaxAttemptsExceededException
        // (JobFailed) while the original worker eventually completes it (JobProcessed).
        // Both events target the same telescope_uuid, so the processed update must not
        // leave behind the failure's exception payload or "failed" tag.
        Telescope::startRecording(false);

        $watcher = new JobWatcher;

        $entry = $watcher->recordJob('redis', 'default', [
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'displayName' => MyDatabaseJob::class,
            'maxTries' => 1,
            'timeout' => 30,
            'data' => ['payload' => 'long-running'],
        ]);

        $job = m::mock(Job::class);
        $job->shouldReceive('payload')->andReturn(['telescope_uuid' => $entry->uuid]);

        $watcher->recordFailedJob(new JobFailed(
            'redis', $job, new Exception(MyDatabaseJob::class.' has been attempted too many times.')
        ));

        $watcher->recordProcessedJob(new JobProcessed('redis', $job));

        $stored = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::JOB, $stored->type);
        $this->assertSame('processed', $stored->content['status']);
        $this->assertNull($stored->content['exception']);

        $hasFailedTag = $this->app['db']->connection('testbench')
            ->table('telescope_entries_tags')
            ->where('entry_uuid', $entry->uuid)
            ->where('tag', 'failed')
            ->exists();

        $this->assertFalse($hasFailedTag, 'The "failed" tag must be removed once the job is processed.');
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

    public function test_job_can_handle_deleted_serialized_model()
    {
        $user = UserFactory::new()->create();

        $this->app->get(Dispatcher::class)->dispatch(
            new MockedDeleteUserJob($user)
        );

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
        ])->run();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::JOB, $entry->type);
        $this->assertSame('processed', $entry->content['status']);
        $this->assertSame('database', $entry->content['connection']);
        $this->assertSame(MockedDeleteUserJob::class, $entry->content['name']);
        $this->assertSame('default', $entry->content['queue']);

        $this->assertSame(sprintf('%s:%s', get_class($user), $user->getKey()), $entry->content['data']['user']);
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

class MockedDeleteUserJob implements ShouldQueue
{
    use SerializesModels;

    public $connection = 'database';

    public $deleteWhenMissingModels = true;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        $this->user->delete();
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
