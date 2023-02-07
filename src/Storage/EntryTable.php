<?php

namespace Laravel\Telescope\Storage;

trait EntryTable
{
    /**
     * Get the entries table name.
     * @return string
     */
    public function getEntriesTableName(): string
    {
        return config('telescope.table_names.entries', 'telescope_entries');
    }

    /**
     * Get the tags table name.
     * @return string
     */
    public function getTagsTableName(): string
    {
        return config('telescope.table_names.tags', 'telescope_entries_tags');
    }

    /**
     * Get the monitoring table name.
     * @return string
     */
    public function getMonitoringTableName(): string
    {
        return config('telescope.table_names.monitoring', 'telescope_monitoring');
    }
}
