<?php

namespace Laravel\Telescope\Tests\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;

class ShowCommandTest extends FeatureTestCase
{
    /**
     * Indicates if console output should be mocked.
     *
     * Disabled so Artisan::output() captures the real command output.
     *
     * @var bool
     */
    public $mockConsoleOutput = false;

    public function test_show_displays_request_entry()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::REQUEST,
            'content' => [
                'method' => 'GET', 'uri' => '/api/users', 'response_status' => 200,
                'duration' => 145, 'memory' => 12, 'controller_action' => 'UserController@index',
                'middleware' => ['web'], 'ip_address' => '127.0.0.1', 'hostname' => 'localhost',
                'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [],
            ],
        ]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $entry->uuid]));
    }

    public function test_show_displays_exception_entry()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::EXCEPTION,
            'content' => [
                'class' => 'InvalidArgumentException', 'message' => 'Something went wrong',
                'file' => 'app/Http/Controllers/UserController.php', 'line' => 38,
                'trace' => [], 'line_preview' => [], 'hostname' => 'localhost', 'occurrences' => 1,
            ],
        ]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $entry->uuid]));
    }

    public function test_show_displays_batch_context()
    {
        $batchId = (string) Str::uuid();

        $request = EntryModelFactory::new()->create([
            'sequence' => 100,
            'type' => EntryType::REQUEST,
            'batch_id' => $batchId,
            'content' => [
                'method' => 'GET', 'uri' => '/api/users', 'response_status' => 200,
                'duration' => 145, 'memory' => 12, 'hostname' => 'localhost',
                'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [],
                'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1',
            ],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 101,
            'type' => EntryType::QUERY,
            'batch_id' => $batchId,
            'content' => [
                'sql' => 'select * from "users"', 'time' => 2.5,
                'connection' => 'testbench', 'slow' => false, 'hostname' => 'localhost',
                'hash' => 'q1',
            ],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 102,
            'type' => EntryType::EXCEPTION,
            'batch_id' => $batchId,
            'content' => [
                'class' => 'RuntimeException', 'message' => 'Test',
                'file' => 'test.php', 'line' => 1, 'trace' => [],
                'hostname' => 'localhost', 'occurrences' => 1,
            ],
        ]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $request->uuid]));
    }

    public function test_show_latest_shortcut()
    {
        EntryModelFactory::new()->create([
            'sequence' => 1,
            'type' => EntryType::REQUEST,
            'created_at' => now()->subMinutes(5),
            'content' => ['method' => 'GET', 'uri' => '/old', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost', 'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1', 'memory' => 8],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 2,
            'type' => EntryType::EXCEPTION,
            'created_at' => now(),
            'content' => ['class' => 'RuntimeException', 'message' => 'Latest', 'file' => 'test.php', 'line' => 1, 'trace' => [], 'hostname' => 'localhost', 'occurrences' => 1],
        ]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => 'latest']));
    }

    public function test_show_latest_type_shortcut()
    {
        $request = EntryModelFactory::new()->create([
            'sequence' => 1,
            'type' => EntryType::REQUEST,
            'content' => ['method' => 'GET', 'uri' => '/api/test', 'response_status' => 200, 'duration' => 100, 'hostname' => 'localhost', 'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1', 'memory' => 8],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 2,
            'type' => EntryType::EXCEPTION,
            'content' => ['class' => 'RuntimeException', 'message' => 'Error', 'file' => 'test.php', 'line' => 1, 'trace' => [], 'hostname' => 'localhost', 'occurrences' => 1],
        ]);

        Artisan::call('telescope:show', ['id' => 'latest:request']);
        $output = Artisan::output();

        $this->assertStringContainsString('/api/test', $output);
        $this->assertStringNotContainsString('RuntimeException', $output);
    }

    public function test_show_type_filter()
    {
        $batchId = (string) Str::uuid();

        $request = EntryModelFactory::new()->create([
            'sequence' => 100,
            'type' => EntryType::REQUEST,
            'batch_id' => $batchId,
            'content' => ['method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost', 'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1', 'memory' => 8],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 101,
            'type' => EntryType::QUERY,
            'batch_id' => $batchId,
            'content' => ['sql' => 'select 1', 'time' => 1.0, 'connection' => 'testbench', 'slow' => false, 'hostname' => 'localhost', 'hash' => 'h1'],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 102,
            'type' => EntryType::CACHE,
            'batch_id' => $batchId,
            'content' => ['type' => 'hit', 'key' => 'test', 'hostname' => 'localhost'],
        ]);

        Artisan::call('telescope:show', ['id' => $request->uuid, '--type' => 'query']);
        $output = Artisan::output();

        $this->assertStringContainsString('select 1', $output);
        $this->assertStringNotContainsString('hit rate', $output);
    }

    public function test_show_batch_context_lists_queries()
    {
        $batchId = (string) Str::uuid();

        $request = EntryModelFactory::new()->create([
            'sequence' => 100,
            'type' => EntryType::REQUEST,
            'batch_id' => $batchId,
            'content' => ['method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost', 'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1', 'memory' => 8],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 101,
            'type' => EntryType::QUERY,
            'batch_id' => $batchId,
            'content' => ['sql' => 'select 1', 'time' => 1.0, 'connection' => 'testbench', 'slow' => false, 'hostname' => 'localhost', 'hash' => 'h1'],
        ]);

        Artisan::call('telescope:show', ['id' => $request->uuid]);
        $output = Artisan::output();

        $this->assertStringContainsString('Related Entries', $output);
        $this->assertStringContainsString('Queries', $output);
        $this->assertStringContainsString('select 1', $output);
    }

    public function test_show_batch_has_query_stats()
    {
        $batchId = (string) Str::uuid();

        $request = EntryModelFactory::new()->create([
            'sequence' => 100,
            'type' => EntryType::REQUEST,
            'batch_id' => $batchId,
            'content' => ['method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost', 'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1', 'memory' => 8],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 101,
            'type' => EntryType::QUERY,
            'batch_id' => $batchId,
            'content' => ['sql' => 'select * from users', 'time' => 150.0, 'connection' => 'testbench', 'slow' => true, 'hostname' => 'localhost', 'hash' => 'dup1'],
        ]);

        EntryModelFactory::new()->create([
            'sequence' => 102,
            'type' => EntryType::QUERY,
            'batch_id' => $batchId,
            'content' => ['sql' => 'select * from users', 'time' => 2.0, 'connection' => 'testbench', 'slow' => false, 'hostname' => 'localhost', 'hash' => 'dup1'],
        ]);

        Artisan::call('telescope:show', ['id' => $request->uuid]);
        $output = Artisan::output();

        $this->assertStringContainsString('2 total, 152ms', $output);
        $this->assertStringContainsString('1 slow', $output);
        $this->assertStringContainsString('1 duplicate groups', $output);
    }

    public function test_show_batch_has_cache_stats()
    {
        $batchId = (string) Str::uuid();

        $request = EntryModelFactory::new()->create([
            'sequence' => 100,
            'type' => EntryType::REQUEST,
            'batch_id' => $batchId,
            'content' => ['method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost', 'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1', 'memory' => 8],
        ]);

        EntryModelFactory::new()->count(3)->create([
            'type' => EntryType::CACHE,
            'batch_id' => $batchId,
            'content' => ['type' => 'hit', 'key' => 'user:1', 'hostname' => 'localhost'],
        ]);

        EntryModelFactory::new()->create([
            'type' => EntryType::CACHE,
            'batch_id' => $batchId,
            'content' => ['type' => 'missed', 'key' => 'user:2', 'hostname' => 'localhost'],
        ]);

        Artisan::call('telescope:show', ['id' => $request->uuid]);

        $this->assertStringContainsString('3 hits, 1 misses — 75% hit rate', Artisan::output());
    }

    public function test_show_entry_not_found()
    {
        $this->assertSame(1, $this->artisan('telescope:show', ['id' => 'nonexistent-uuid']));
    }

    public function test_show_latest_with_no_entries()
    {
        $this->assertSame(1, $this->artisan('telescope:show', ['id' => 'latest']));
    }

    public function test_show_latest_type_with_no_matching_entries()
    {
        EntryModelFactory::new()->create(['type' => EntryType::REQUEST, 'content' => [
            'method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost',
            'payload' => [], 'response' => [], 'headers' => [], 'response_headers' => [], 'session' => [], 'middleware' => [], 'ip_address' => '127.0.0.1', 'memory' => 8,
        ]]);

        $this->assertSame(1, $this->artisan('telescope:show', ['id' => 'latest:exception']));
    }

    public function test_show_displays_event_listeners()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::EVENT,
            'content' => [
                'name' => 'App\\Events\\OrderShipped', 'broadcast' => false, 'hostname' => 'localhost',
                'listeners' => [['name' => 'App\\Listeners\\SendShipmentNotification@handle', 'queued' => true]],
                'payload' => [],
            ],
        ]);

        Artisan::call('telescope:show', ['id' => $entry->uuid]);

        $this->assertStringContainsString('SendShipmentNotification@handle (queued)', Artisan::output());
    }

    public function test_show_displays_mail_addresses()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::MAIL,
            'content' => [
                'mailable' => 'App\\Mail\\Welcome', 'subject' => 'Welcome', 'queued' => false, 'hostname' => 'localhost',
                'to' => ['alice@example.com' => 'Alice'], 'from' => ['noreply@example.com' => null],
            ],
        ]);

        Artisan::call('telescope:show', ['id' => $entry->uuid]);

        $this->assertStringContainsString('Alice <alice@example.com>', Artisan::output());
    }
}
