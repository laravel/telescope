<?php

namespace Laravel\Telescope\Tests\Storage;

use Laravel\Telescope\Storage\DatabaseEntriesRepository;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Tests\FeatureTestCase;

class DatabaseEntriesRepositoryTest extends FeatureTestCase
{
    public function test_find_entry_by_uuid()
    {
        $this->loadFactoriesUsing($this->app, __DIR__ . '/../../src/Storage/factories');

        $entry = factory(EntryModel::class)->create();

        $repository = new DatabaseEntriesRepository('testbench');

        $result = $repository->find($entry->uuid)->jsonSerialize();

        self::assertSame($entry->uuid, $result['id']);
        self::assertSame($entry->batch_id, $result['batch_id']);
        self::assertSame($entry->type, $result['type']);
        self::assertSame($entry->content, $result['content']);

        // Why is sequence always null? DatabaseEntriesRepository::class#L60
        self::assertNull($result['sequence']);
    }
}
