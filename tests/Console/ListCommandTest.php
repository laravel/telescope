<?php

namespace Laravel\Telescope\Tests\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;

class ListCommandTest extends FeatureTestCase
{
    /**
     * Indicates if console output should be mocked.
     *
     * Disabled so Artisan::output() captures the real command output.
     *
     * @var bool
     */
    public $mockConsoleOutput = false;

    public function test_list_filters_by_type()
    {
        EntryModelFactory::new()->create(['type' => EntryType::REQUEST, 'content' => [
            'method' => 'GET', 'uri' => '/test', 'response_status' => 200,
            'duration' => 100, 'hostname' => 'localhost',
        ]]);
        EntryModelFactory::new()->create(['type' => EntryType::EXCEPTION, 'content' => [
            'class' => 'RuntimeException', 'message' => 'fail', 'hostname' => 'localhost',
        ]]);

        Artisan::call('telescope:list', ['type' => 'request']);
        $output = Artisan::output();

        $this->assertStringContainsString('/test', $output);
        $this->assertStringContainsString('Showing 1 entries', $output);
    }

    public function test_list_validates_type_argument()
    {
        $this->assertSame(1, $this->artisan('telescope:list', ['type' => 'foobar']));
    }

    public function test_list_filters_by_batch()
    {
        $batchId = (string) Str::uuid();

        EntryModelFactory::new()->create([
            'type' => EntryType::REQUEST,
            'batch_id' => $batchId,
            'content' => ['method' => 'GET', 'uri' => '/a', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost'],
        ]);
        EntryModelFactory::new()->create([
            'type' => EntryType::REQUEST,
            'content' => ['method' => 'POST', 'uri' => '/b', 'response_status' => 201, 'duration' => 80, 'hostname' => 'localhost'],
        ]);

        Artisan::call('telescope:list', ['type' => 'request', '--batch' => $batchId]);
        $output = Artisan::output();

        $this->assertStringContainsString('/a', $output);
        $this->assertStringNotContainsString('/b', $output);
    }

    public function test_list_respects_limit()
    {
        EntryModelFactory::new()->count(5)->create([
            'type' => EntryType::REQUEST,
            'content' => ['method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost'],
        ]);

        Artisan::call('telescope:list', ['type' => 'request', '--limit' => 2]);
        $output = Artisan::output();

        $this->assertStringContainsString('Showing 2 entries', $output);
        $this->assertStringContainsString('--before=', $output);
    }

    public function test_list_shows_warning_when_empty()
    {
        $this->assertSame(0, $this->artisan('telescope:list', ['type' => 'request']));
    }

    public function test_list_shows_all_entry_types()
    {
        EntryModelFactory::new()->create(['type' => EntryType::REQUEST, 'content' => [
            'method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost',
        ]]);
        EntryModelFactory::new()->create(['type' => EntryType::EXCEPTION, 'content' => [
            'class' => 'RuntimeException', 'message' => 'fail', 'hostname' => 'localhost',
        ]]);

        Artisan::call('telescope:list');
        $output = Artisan::output();

        $this->assertStringContainsString('Showing 2 entries', $output);
        $this->assertStringContainsString('request', $output);
        $this->assertStringContainsString('exception', $output);
    }

    public function test_list_cursor_has_more_false_when_all_returned()
    {
        EntryModelFactory::new()->count(3)->create([
            'type' => EntryType::REQUEST,
            'content' => ['method' => 'GET', 'uri' => '/test', 'response_status' => 200, 'duration' => 50, 'hostname' => 'localhost'],
        ]);

        Artisan::call('telescope:list', ['type' => 'request', '--limit' => 20]);

        $this->assertStringContainsString('No more entries', Artisan::output());
    }
}
