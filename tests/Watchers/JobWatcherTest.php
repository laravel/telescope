<?php

namespace Laravel\Telescope\Tests\Watchers;

use Exception;
use Laravel\Telescope\EntryType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Laravel\Telescope\Watchers\JobWatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Telescope\Tests\FeatureTestCase;

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
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::JOB, $entry->type);
        $this->assertSame('processed', $entry->content['status']);
        $this->assertSame('database', $entry->content['connection']);
        $this->assertSame(MyDatabaseJob::class, $entry->content['name']);
        $this->assertSame('on-demand', $entry->content['queue']);
        $this->assertSame('Awesome Laravel', $entry->content['data']['payload']);
    }

    public function test_failed_jobs_register_entry()
    {
        $this->app->get(Dispatcher::class)->dispatch(
            new MyFailedDatabaseJob('I never watched Star Wars.')
        );

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--once' => true,
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::JOB, $entry->type);
        $this->assertSame('failed', $entry->content['status']);
        $this->assertSame('database', $entry->content['connection']);
        $this->assertSame(MyFailedDatabaseJob::class, $entry->content['name']);
        $this->assertSame('default', $entry->content['queue']);
        $this->assertSame('I never watched Star Wars.', $entry->content['data']['message']);
        $this->assertArrayHasKey('exception', $entry->content);
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

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
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
