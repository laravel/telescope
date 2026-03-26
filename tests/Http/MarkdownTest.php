<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Testing\TestResponse;
use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Tests\FeatureTestCase;
use Orchestra\Testbench\Http\Middleware\VerifyCsrfToken;

class MarkdownTest extends FeatureTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([Authorize::class, VerifyCsrfToken::class, ValidateCsrfToken::class, PreventRequestForgery::class]);
    }

    protected function assertMarkdownContains(TestResponse $response, array $expectedStrings): void
    {
        $response->assertOk();
        $this->assertStringStartsWith('text/markdown', $response->headers->get('Content-Type'));

        foreach ($expectedStrings as $expected) {
            $this->assertStringContainsString($expected, $response->content());
        }
    }

    public function test_exception_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::EXCEPTION,
            'content' => [
                'class' => 'RuntimeException',
                'file' => 'app/Http/Controllers/UserController.php',
                'line' => 42,
                'message' => 'Something went wrong',
                'context' => null,
                'trace' => [
                    ['file' => 'app/Http/Controllers/UserController.php', 'line' => 42],
                    ['file' => 'vendor/laravel/framework/Router.php', 'line' => 100],
                ],
                'line_preview' => [],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/exceptions/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Exception Details',
            '**Type:** RuntimeException',
            '**Message:** Something went wrong',
            '**Location:** app/Http/Controllers/UserController.php:42',
        ]);
    }

    public function test_request_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::REQUEST,
            'content' => [
                'method' => 'POST',
                'uri' => '/api/orders',
                'controller_action' => 'OrderController@store',
                'middleware' => ['auth'],
                'headers' => ['accept' => 'application/json'],
                'payload' => ['product_id' => 5],
                'session' => [],
                'response_headers' => [],
                'response_status' => 201,
                'response' => ['id' => 1],
                'duration' => 150,
                'memory' => 24,
                'ip_address' => '127.0.0.1',
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/requests/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Request Details',
            '**Method:** POST',
            '**URL:** /api/orders',
            '**Status:** 201',
        ]);
    }

    public function test_query_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::QUERY,
            'content' => [
                'connection' => 'mysql',
                'sql' => 'select * from "users" where "id" = 1',
                'time' => '12.34',
                'slow' => false,
                'file' => 'app/Models/User.php',
                'line' => 25,
                'hash' => 'abc123',
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/queries/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'select * from',
            '**Connection:** mysql',
            '**Duration:** 12.34ms',
        ]);
    }

    public function test_job_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::JOB,
            'content' => [
                'name' => 'App\\Jobs\\ProcessOrder',
                'status' => 'processed',
                'connection' => 'redis',
                'queue' => 'default',
                'tries' => 3,
                'timeout' => null,
                'data' => ['orderId' => 123],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/jobs/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Job Details',
            '**Job:** App\\Jobs\\ProcessOrder',
            '**Status:** processed',
        ]);
    }

    public function test_log_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::LOG,
            'content' => [
                'level' => 'error',
                'message' => 'Payment failed for order 123',
                'context' => ['order_id' => 123],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/logs/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'ERROR',
            'Payment failed for order 123',
        ]);
    }

    public function test_mail_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::MAIL,
            'content' => [
                'mailable' => 'App\\Mail\\OrderConfirmation',
                'queued' => false,
                'from' => ['noreply@example.com' => 'Example App'],
                'to' => ['user@example.com' => 'John Doe'],
                'cc' => null,
                'bcc' => null,
                'replyTo' => null,
                'subject' => 'Your Order #123',
                'html' => '<html></html>',
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/mail/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'OrderConfirmation',
            'Your Order #123',
            'user@example.com',
        ]);
    }

    public function test_event_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::EVENT,
            'content' => [
                'name' => 'App\\Events\\OrderPlaced',
                'broadcast' => false,
                'payload' => ['order_id' => 123],
                'listeners' => ['App\\Listeners\\SendConfirmation'],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/events/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'OrderPlaced',
            'SendConfirmation',
        ]);
    }

    public function test_cache_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::CACHE,
            'content' => [
                'type' => 'hit',
                'key' => 'users.1.profile',
                'value' => 'cached-data',
                'expiration' => null,
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/cache/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'Hit',
            'users.1.profile',
        ]);
    }

    public function test_redis_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::REDIS,
            'content' => [
                'connection' => 'default',
                'command' => 'GET telescope:pause-recording',
                'time' => '0.15',
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/redis/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'GET telescope:pause-recording',
            '**Connection:** default',
            '**Duration:** 0.15ms',
        ]);
    }

    public function test_model_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::MODEL,
            'content' => [
                'action' => 'updated',
                'model' => 'App\\Models\\User:1',
                'changes' => ['name' => ['old', 'new']],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/models/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Model Details',
            '**Action:** updated',
            '**Model:** App\\Models\\User:1',
        ]);
    }

    public function test_notification_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::NOTIFICATION,
            'content' => [
                'notification' => 'App\\Notifications\\InvoicePaid',
                'queued' => true,
                'notifiable' => 'App\\Models\\User:1',
                'channel' => 'mail',
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/notifications/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Notification Details',
            '**Notification:** App\\Notifications\\InvoicePaid',
            '**Channel:** mail',
        ]);
    }

    public function test_gate_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::GATE,
            'content' => [
                'ability' => 'update',
                'result' => 'denied',
                'message' => 'This action is unauthorized.',
                'file' => null,
                'line' => null,
                'arguments' => ['App\\Models\\Post'],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/gates/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Gate Details',
            '**Ability:** update',
            '**Result:** Denied',
        ]);
    }

    public function test_command_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::COMMAND,
            'content' => [
                'command' => 'migrate:fresh',
                'exit_code' => 0,
                'arguments' => ['command' => 'migrate:fresh'],
                'options' => ['seed' => true],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/commands/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Command Details',
            '**Command:** migrate:fresh',
            '**Exit Code:** 0',
        ]);
    }

    public function test_schedule_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::SCHEDULED_TASK,
            'content' => [
                'command' => 'inspire',
                'description' => 'Display an inspiring quote',
                'expression' => '* * * * *',
                'timezone' => 'UTC',
                'user' => null,
                'output' => null,
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/schedule/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'inspire',
            '* * * * *',
        ]);
    }

    public function test_batch_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::BATCH,
            'content' => [
                'name' => 'Import Users',
                'connection' => 'redis',
                'queue' => 'default',
                'totalJobs' => 100,
                'pendingJobs' => 5,
                'failedJobs' => 2,
                'progress' => 93,
                'cancelledAt' => null,
                'finishedAt' => null,
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/batches/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Batch Details',
            '**Name:** Import Users',
            '**Total Jobs:** 100',
        ]);
    }

    public function test_view_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::VIEW,
            'content' => [
                'name' => 'orders.show',
                'path' => '/resources/views/orders/show.blade.php',
                'data' => ['order' => ['id' => 1]],
                'composers' => [],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/views/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            'orders.show',
            'show.blade.php',
        ]);
    }

    public function test_client_request_markdown()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::CLIENT_REQUEST,
            'content' => [
                'method' => 'POST',
                'uri' => 'https://api.stripe.com/v1/charges',
                'response_status' => 200,
                'duration' => 450,
                'payload' => ['amount' => 9999],
                'headers' => ['content-type' => 'application/json'],
                'response' => ['id' => 'ch_123'],
                'response_headers' => [],
                'hostname' => 'web-01',
            ],
        ]);

        $response = $this->get("/telescope/telescope-api/client-requests/{$entry->uuid}/markdown");

        $this->assertMarkdownContains($response, [
            '# Client Request Details',
            '**Method:** POST',
            '**URL:** https://api.stripe.com/v1/charges',
        ]);
    }

    public function test_markdown_returns_404_for_entry_without_template()
    {
        $entry = EntryModelFactory::new()->create([
            'type' => EntryType::DUMP,
            'content' => ['dump' => 'test'],
        ]);

        $this->get("/telescope/telescope-api/logs/{$entry->uuid}/markdown")
            ->assertNotFound();
    }
}
