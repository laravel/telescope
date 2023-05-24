<?php

namespace Laravel\Telescope\Tests\Storage;

use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Tests\FeatureTestCase;

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
}
