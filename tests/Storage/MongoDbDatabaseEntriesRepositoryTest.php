<?php

namespace Laravel\Telescope\Tests\Storage;

use Illuminate\Support\Str;
use Laravel\Telescope\Database\Factories\MongoDbEntryModelFactory;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\IncomingExceptionEntry;
use Laravel\Telescope\Storage\Mongodb\MongoDbEntriesRepository;
use Laravel\Telescope\Tests\FeatureTestCase;

class MongoDbDatabaseEntriesRepositoryTest extends FeatureTestCase
{

    protected function getEnvironmentSetUp($app)
    {
        $this->markTestSkippedUnless(env('MONGODB_URI'), 'MONGODB_URI not provided, skipping MongoDB tests.');

        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('telescope.storage.database.connection', 'mongodb');
        $config->set('telescope.driver', 'mongodb');
        $config->set('database.connections.mongodb', [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_URI'),
            'database' => env('MONGODB_DATABASE', 'testing'),
        ]);
    }

    public function test_find_entry_by_uuid()
    {
        $entry = MongoDbEntryModelFactory::new()->create();

        $repository = new MongoDbEntriesRepository('mongodb');

        $result = $repository->find($entry->uuid)->jsonSerialize();

        $this->assertSame($entry->uuid, $result['id']);
        $this->assertSame($entry->batch_id, $result['batch_id']);
        $this->assertSame($entry->type, $result['type']);
        $this->assertSame($entry->content, $result['content']);

        // Why is sequence always null? DatabaseEntriesRepository::class#L60
        $this->assertNull($result['sequence']);
    }

    public function test_update()
    {
        $entry = MongoDbEntryModelFactory::new()->create();

        $repository = new MongoDbEntriesRepository('mongodb');

        $result = $repository->find($entry->uuid)->jsonSerialize();

        $failedUpdates = $repository->update(collect([
            new EntryUpdate($result['id'], $result['type'], ['content' => ['foo' => 'bar']]),
            new EntryUpdate('missing-id', $result['type'], ['content' => ['foo' => 'bar']]),
        ]));

        $this->assertCount(1, $failedUpdates);
        $this->assertSame('missing-id', $failedUpdates->first()->uuid);
    }

    public function test_store_binary_content()
    {
        $batchId = Str::uuid();
        $exception = new \Exception('message');

        $entries = collect([
            (new IncomingEntry(['message' => gzcompress('message')]))->batchId($batchId)->type(EntryType::LOG),
            (new IncomingExceptionEntry($exception, [
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'message' => gzcompress($exception->getMessage()),
            ]))->batchId($batchId)->type(EntryType::EXCEPTION),
        ]);

        $repository = new MongoDbEntriesRepository('mongodb');

        $repository->store($entries);

        $entries->each(function ($entry) {
            $this->assertDatabaseMissing('telescope_entries', [
                'uuid'    => $entry->uuid,
                'content' => false,
            ]);
        });
    }
}
