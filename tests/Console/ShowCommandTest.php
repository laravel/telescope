<?php

namespace Laravel\Telescope\Tests\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;

class ShowCommandTest extends FeatureTestCase
{
    use CreatesTelescopeEntries;

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
        $entry = $this->createRequest([
            'uri' => '/api/users', 'duration' => 145, 'controller_action' => 'UserController@index',
            'middleware' => ['web'],
        ]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $entry->uuid]));

        $output = Artisan::output();

        $this->assertStringContainsString('Request: GET /api/users -> 200', $output);
        $this->assertStringContainsString('UserController@index', $output);
        $this->assertStringContainsString('145ms', $output);
        $this->assertStringContainsString('127.0.0.1', $output);
    }

    public function test_show_displays_exception_entry()
    {
        $entry = $this->createException([
            'class' => 'InvalidArgumentException', 'message' => 'Something went wrong',
            'file' => 'app/Http/Controllers/UserController.php', 'line' => 38,
        ]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $entry->uuid]));

        $output = Artisan::output();

        $this->assertStringContainsString('Exception: InvalidArgumentException', $output);
        $this->assertStringContainsString('Something went wrong', $output);
        $this->assertStringContainsString('UserController.php:38', $output);
    }

    public function test_show_displays_a_failed_job_with_its_exception()
    {
        $job = $this->createEntry(EntryType::JOB, [
            'name' => 'App\Jobs\SyncOrders', 'status' => 'failed', 'queue' => 'default',
            'connection' => 'redis', 'tries' => 3, 'data' => ['order_id' => 7],
            'exception' => [
                'message' => 'Payment gateway timed out',
                'trace' => [['file' => '/app/Jobs/SyncOrders.php', 'line' => 22]],
            ],
        ]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $job->uuid]));

        $output = Artisan::output();

        $this->assertStringContainsString('Job: SyncOrders [failed]', $output);
        $this->assertStringContainsString('Payment gateway timed out', $output);
        $this->assertStringContainsString('/app/Jobs/SyncOrders.php:22', $output);
    }

    public function test_show_displays_batch_context()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest(['uri' => '/api/users'], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createQuery(['sql' => 'select * from "users"'], ['sequence' => 101, 'batch_id' => $batchId]);
        $this->createException([], ['sequence' => 102, 'batch_id' => $batchId]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $request->uuid]));

        $output = Artisan::output();

        $this->assertStringContainsString('Related Entries - batch '.substr($batchId, 0, 8), $output);
        $this->assertStringContainsString('select * from "users"', $output);
        $this->assertStringContainsString('Exceptions - 1', $output);
        $this->assertStringContainsString('RuntimeException: Test', $output);
    }

    public function test_show_batch_context_of_an_exception_includes_its_request()
    {
        $batchId = (string) Str::uuid();

        $this->createRequest(['method' => 'POST', 'uri' => '/orders', 'response_status' => 500], ['sequence' => 100, 'batch_id' => $batchId]);
        $exception = $this->createException(['message' => 'Boom'], ['sequence' => 101, 'batch_id' => $batchId]);

        Artisan::call('telescope:show', ['id' => $exception->uuid]);

        $this->assertStringContainsString('POST /orders -> 500', Artisan::output());
    }

    public function test_show_renders_batch_entry_types_without_a_dedicated_section()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createEntry(EntryType::DUMP, ['dump' => '<pre>needle</pre>'], ['sequence' => 101, 'batch_id' => $batchId]);
        $this->createEntry(EntryType::VIEW, ['name' => 'welcome', 'path' => '/v.blade.php'], ['sequence' => 102, 'batch_id' => $batchId]);

        Artisan::call('telescope:show', ['id' => $request->uuid]);
        $output = Artisan::output();

        $this->assertStringContainsString('Dumps (1)', $output);
        $this->assertStringContainsString('needle', $output);
        $this->assertStringContainsString('Views (1)', $output);
        $this->assertStringContainsString('welcome', $output);
    }

    public function test_show_batch_entries_are_chronological()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);

        foreach (['select first', 'select second'] as $index => $sql) {
            $this->createQuery(['sql' => $sql, 'hash' => "h{$index}"], ['sequence' => 101 + $index, 'batch_id' => $batchId]);
        }

        Artisan::call('telescope:show', ['id' => $request->uuid]);
        $output = Artisan::output();

        $this->assertLessThan(strpos($output, 'select second'), strpos($output, 'select first'));
    }

    public function test_show_batch_flags_slow_and_duplicate_queries()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);

        foreach ([[101, 150.0, true], [102, 2.0, false]] as [$sequence, $time, $slow]) {
            $this->createQuery([
                'sql' => 'select * from users', 'time' => $time, 'slow' => $slow, 'hash' => 'dup1',
                'file' => '/app/Http/Controllers/UserController.php', 'line' => 3,
            ], ['sequence' => $sequence, 'batch_id' => $batchId]);
        }

        Artisan::call('telescope:show', ['id' => $request->uuid]);
        $output = Artisan::output();

        $this->assertStringContainsString('2 total, 152ms', $output);
        $this->assertStringContainsString('1 slow', $output);
        $this->assertStringContainsString('1 duplicate group', $output);
        $this->assertStringContainsString('SLOW', $output);
        $this->assertStringContainsString('DUP', $output);
        $this->assertStringContainsString('UserController.php:3', $output);
    }

    public function test_show_batch_reports_the_cache_hit_rate()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);

        foreach (['hit', 'hit', 'hit', 'missed', 'set'] as $action) {
            $this->createEntry(EntryType::CACHE, ['type' => $action, 'key' => 'user:1'], ['batch_id' => $batchId]);
        }

        Artisan::call('telescope:show', ['id' => $request->uuid]);

        $this->assertStringContainsString('3 hits, 1 misses - 75% hit rate', Artisan::output());
    }

    public function test_show_batch_omits_the_hit_rate_when_no_cache_lookups_were_made()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createEntry(EntryType::CACHE, ['type' => 'set', 'key' => 'user:1'], ['batch_id' => $batchId]);

        Artisan::call('telescope:show', ['id' => $request->uuid]);
        $output = Artisan::output();

        $this->assertStringContainsString('Cache - 0 hits, 0 misses', $output);
        $this->assertStringNotContainsString('hit rate', $output);
    }

    public function test_show_latest_shortcut()
    {
        $this->createRequest(['uri' => '/old'], ['sequence' => 1]);
        $this->createException(['message' => 'Latest'], ['sequence' => 2]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => 'latest']));

        $output = Artisan::output();

        $this->assertStringContainsString('Exception: RuntimeException', $output);
        $this->assertStringContainsString('Latest', $output);
        $this->assertStringNotContainsString('/old', $output);
    }

    public function test_show_latest_type_shortcut()
    {
        $this->createRequest(['uri' => '/api/test'], ['sequence' => 1]);
        $this->createException(['message' => 'Error'], ['sequence' => 2]);

        Artisan::call('telescope:show', ['id' => 'latest:request']);
        $output = Artisan::output();

        $this->assertStringContainsString('/api/test', $output);
        $this->assertStringNotContainsString('RuntimeException', $output);
    }

    public function test_show_type_option_filters_the_batch()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createQuery([], ['sequence' => 101, 'batch_id' => $batchId]);
        $this->createEntry(EntryType::CACHE, ['type' => 'hit', 'key' => 'test'], ['sequence' => 102, 'batch_id' => $batchId]);

        Artisan::call('telescope:show', ['id' => $request->uuid, '--type' => 'query']);
        $output = Artisan::output();

        $this->assertStringContainsString('select 1', $output);
        $this->assertStringNotContainsString('hit rate', $output);
    }

    public function test_show_type_option_accepts_a_list_with_spaces()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createQuery([], ['sequence' => 101, 'batch_id' => $batchId]);
        $this->createEntry(EntryType::CACHE, ['type' => 'hit', 'key' => 'spaced'], ['sequence' => 102, 'batch_id' => $batchId]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $request->uuid, '--type' => 'query, cache']));

        $output = Artisan::output();

        $this->assertStringContainsString('select 1', $output);
        $this->assertStringContainsString('spaced', $output);
    }

    public function test_show_reports_when_the_batch_has_no_entries_of_the_requested_type()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createQuery([], ['sequence' => 101, 'batch_id' => $batchId]);

        $this->assertSame(0, $this->artisan('telescope:show', ['id' => $request->uuid, '--type' => 'log']));

        $this->assertStringContainsString('No batch entries of type log.', Artisan::output());
    }

    public function test_show_full_option_disables_truncation()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createQuery([
            'sql' => 'select * from users where '.str_repeat('id = 1 or ', 20).'id = 2',
        ], ['sequence' => 101, 'batch_id' => $batchId]);

        Artisan::call('telescope:show', ['id' => $request->uuid]);
        $this->assertStringNotContainsString('id = 2', Artisan::output());

        Artisan::call('telescope:show', ['id' => $request->uuid, '--full' => true]);
        $this->assertStringContainsString('id = 2', Artisan::output());
    }

    public function test_show_omits_units_for_values_the_entry_does_not_have()
    {
        $job = $this->createEntry(EntryType::JOB, [
            'name' => 'App\Jobs\SyncOrders', 'status' => 'processed', 'queue' => 'default',
            'connection' => 'redis', 'tries' => null, 'timeout' => null, 'data' => [],
        ]);

        Artisan::call('telescope:show', ['id' => $job->uuid]);

        $this->assertStringNotContainsString('Timeout', Artisan::output());
    }

    public function test_show_displays_event_listeners()
    {
        $entry = $this->createEntry(EntryType::EVENT, [
            'name' => 'App\Events\OrderShipped', 'broadcast' => false, 'payload' => [],
            'listeners' => [['name' => 'App\Listeners\SendShipmentNotification@handle', 'queued' => true]],
        ]);

        Artisan::call('telescope:show', ['id' => $entry->uuid]);

        $this->assertStringContainsString('SendShipmentNotification@handle (queued)', Artisan::output());
    }

    public function test_show_displays_mail_addresses()
    {
        $entry = $this->createEntry(EntryType::MAIL, [
            'mailable' => 'App\Mail\Welcome', 'subject' => 'Welcome', 'queued' => false,
            'to' => ['alice@example.com' => 'Alice'], 'from' => ['noreply@example.com' => null],
        ]);

        Artisan::call('telescope:show', ['id' => $entry->uuid]);

        $this->assertStringContainsString('Alice <alice@example.com>', Artisan::output());
    }

    public function test_show_outputs_json()
    {
        $batchId = (string) Str::uuid();

        $request = $this->createRequest([], ['sequence' => 100, 'batch_id' => $batchId]);
        $this->createQuery([], ['sequence' => 101, 'batch_id' => $batchId]);
        $this->createEntry(EntryType::CACHE, ['type' => 'hit', 'key' => 'test'], ['sequence' => 102, 'batch_id' => $batchId]);

        Artisan::call('telescope:show', ['id' => $request->uuid, '--json' => true, '--type' => 'query']);
        $json = json_decode(Artisan::output(), true);

        $this->assertSame($request->uuid, $json['entry']['id']);
        $this->assertCount(1, $json['batch']);
        $this->assertSame('select 1', $json['batch'][0]['content']['sql']);
    }

    public function test_show_entry_not_found()
    {
        $this->assertSame(1, $this->artisan('telescope:show', ['id' => 'nonexistent-uuid']));
        $this->assertStringContainsString('Entry not found: nonexistent-uuid', Artisan::output());
    }

    public function test_show_latest_with_no_entries()
    {
        $this->assertSame(1, $this->artisan('telescope:show', ['id' => 'latest']));
        $this->assertStringContainsString('No entries found.', Artisan::output());
    }

    public function test_show_latest_type_with_no_matching_entries()
    {
        $this->createRequest();

        $this->assertSame(1, $this->artisan('telescope:show', ['id' => 'latest:exception']));
        $this->assertStringContainsString('No exception entries found.', Artisan::output());
    }

    public function test_show_validates_latest_type()
    {
        $this->assertSame(1, $this->artisan('telescope:show', ['id' => 'latest:foobar']));
        $this->assertStringContainsString('Invalid entry type: foobar', Artisan::output());
    }

    public function test_show_validates_type_option()
    {
        $entry = $this->createRequest();

        $this->assertSame(1, $this->artisan('telescope:show', ['id' => $entry->uuid, '--type' => 'foobar']));
        $this->assertStringContainsString('Invalid entry type: foobar', Artisan::output());
    }
}
