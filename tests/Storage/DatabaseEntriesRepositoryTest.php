<?php

namespace Laravel\Telescope\Tests\Storage;

use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\IncomingExceptionEntry;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Database\Factories\EntryModelFactory;

class DatabaseEntriesRepositoryTest extends FeatureTestCase
{
    public function test_find_entry_by_uuid()
    {
        $entry = EntryModelFactory::new()->create();

        $repository = new DatabaseEntriesRepository('testbench');

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
        $entry = EntryModelFactory::new()->create();

        $repository = new DatabaseEntriesRepository('testbench');

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
                'file' => $exception->getFile(), 
                'line' => $exception->getLine(), 
                'message' => gzcompress($exception->getMessage())
            ]))->batchId($batchId)->type(EntryType::EXCEPTION)
        ]);

        $repository = new DatabaseEntriesRepository('testbench');

        $repository->store($entries);

        $entries->each(function ($entry) {
            $this->assertDatabaseMissing('telescope_entries', [
                'uuid' => $entry->uuid,
                'content' => false
            ]);
        });
    }
}
