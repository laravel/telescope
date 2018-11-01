<?php

namespace Laravel\Telescope\Tests\Watchers;

use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\JobWatcher;

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

    protected function setUp()
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

        sleep(1);

        $entry = $this->loadTelescopeEntries()->first();

        self::assertSame(EntryType::JOB, $entry->type);
        self::assertSame('processed', $entry->content['status']);
        self::assertSame('database', $entry->content['connection']);
        self::assertSame(MyDatabaseJob::class, $entry->content['name']);
        self::assertSame('on-demand', $entry->content['queue']);
        self::assertSame('Awesome Laravel', $entry->content['data']['payload']);
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

        sleep(1);

        $entry = $this->loadTelescopeEntries()->first();

        self::assertSame(EntryType::JOB, $entry->type);
        self::assertSame('failed', $entry->content['status']);
        self::assertSame('database', $entry->content['connection']);
        self::assertSame(MyFailedDatabaseJob::class, $entry->content['name']);
        self::assertSame('default', $entry->content['queue']);
        self::assertSame('I never watched Star Wars.', $entry->content['data']['message']);
        self::assertArrayHasKey('exception', $entry->content);
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
