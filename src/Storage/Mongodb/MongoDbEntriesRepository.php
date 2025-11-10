<?php

namespace Laravel\Telescope\Storage\Mongodb;

use Laravel\Telescope\Storage\DatabaseEntriesRepository;

class MongoDbEntriesRepository extends DatabaseEntriesRepository
{
    protected static string $entryModel = MongoDbEntryModel::class;

    public function clear()
    {
        $this->table('telescope_entries')->truncate();
        $this->table('telescope_monitoring')->truncate();
    }
}
