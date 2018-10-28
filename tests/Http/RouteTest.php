<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Tests\FeatureTestCase;
use Orchestra\Testbench\Http\Middleware\VerifyCsrfToken;

class RouteTest extends FeatureTestCase
{
    use WithFaker;

    protected function setUp()
    {
        parent::setUp();

        $this->withoutMiddleware([Authorize::class, VerifyCsrfToken::class]);
    }

    public function telescopeIndexRoutesProvider()
    {
        return [
            'Queries' => ['/telescope/telescope-api/queries', EntryType::QUERY],
            'Models'  => ['/telescope/telescope-api/models', EntryType::MODEL],
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
        $entry = EntryModel::query()->create([
            'sequence' => random_int(1, 10000),
            'uuid' => $this->faker->uuid,
            'batch_id' => $this->faker->uuid,
            'type' => $entryType,
            'content' => [$this->faker->word => $this->faker->word],
        ]);

        $this->post($endpoint)
            ->assertSuccessful()
            ->assertJsonExactFragment($entry->uuid, 'entries.0.id')
            ->assertJsonExactFragment($entryType, 'entries.0.type')
            ->assertJsonExactFragment($entry->sequence, 'entries.0.sequence')
            ->assertJsonExactFragment($entry->batch_id, 'entries.0.batch_id');
    }
}
