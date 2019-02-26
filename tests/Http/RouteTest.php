<?php

namespace Laravel\Telescope\Tests\Http;

use Laravel\Telescope\EntryType;
use PHPUnit\Framework\Assert as PHPUnit;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\TestResponse;
use Laravel\Telescope\Http\Middleware\Authorize;
use Orchestra\Testbench\Http\Middleware\VerifyCsrfToken;

class RouteTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([Authorize::class, VerifyCsrfToken::class]);

        $this->registerAssertJsonExactFragmentMacro();
    }

    public function telescopeIndexRoutesProvider()
    {
        return [
            'Mail' => ['/telescope/telescope-api/mail', EntryType::MAIL],
            'Exceptions' => ['/telescope/telescope-api/exceptions', EntryType::EXCEPTION],
            'Dumps' => ['/telescope/telescope-api/dumps', EntryType::DUMP],
            'Logs' => ['/telescope/telescope-api/logs', EntryType::LOG],
            'Notifications' => ['/telescope/telescope-api/notifications', EntryType::NOTIFICATION],
            'Jobs' => ['/telescope/telescope-api/jobs', EntryType::JOB],
            'Events' => ['/telescope/telescope-api/events', EntryType::EVENT],
            'Cache' => ['/telescope/telescope-api/cache', EntryType::CACHE],
            'Queries' => ['/telescope/telescope-api/queries', EntryType::QUERY],
            'Models' => ['/telescope/telescope-api/models', EntryType::MODEL],
            'Request' => ['/telescope/telescope-api/requests', EntryType::REQUEST],
            'Commands' => ['/telescope/telescope-api/commands', EntryType::COMMAND],
            'Schedule' => ['/telescope/telescope-api/schedule', EntryType::SCHEDULED_TASK],
            'Redis' => ['/telescope/telescope-api/redis', EntryType::REDIS],
        ];
    }

    /**
     * @dataProvider telescopeIndexRoutesProvider
     */
    public function test_route($endpoint)
    {
        $this->post($endpoint)
            ->assertSuccessful()
            ->assertJsonStructure(['entries' => []]);
    }

    /**
     * @dataProvider telescopeIndexRoutesProvider
     */
    public function test_simple_list_of_entries($endpoint, $entryType)
    {
        $this->loadFactoriesUsing($this->app, __DIR__.'/../../src/Storage/factories');

        $entry = factory(EntryModel::class)->create(['type' => $entryType]);

        $this->post($endpoint)
            ->assertSuccessful()
            ->assertJsonExactFragment($entry->uuid, 'entries.0.id')
            ->assertJsonExactFragment($entryType, 'entries.0.type')
            ->assertJsonExactFragment($entry->sequence, 'entries.0.sequence')
            ->assertJsonExactFragment($entry->batch_id, 'entries.0.batch_id');
    }

    private function registerAssertJsonExactFragmentMacro()
    {
        TestResponse::macro('assertJsonExactFragment', function ($expected, $key) {
            $jsonResponse = $this->json();

            PHPUnit::assertEquals(
                $expected,
                $actualValue = data_get($jsonResponse, $key),
                "Failed asserting that [$actualValue] matches expected [$expected].".PHP_EOL.PHP_EOL.
                json_encode($jsonResponse)
            );

            return $this;
        });
    }

    public function test_named_route()
    {
        $this->assertEquals(
            url(config('telescope.path')),
            route('telescope')
        );
    }
}
