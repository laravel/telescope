<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;

class DatabaseEntriesRepository implements Contract
{
    /**
     * Find the entry with the given ID.
     *
     * @param  mixed  $id
     * @return \Laravel\Telescope\EntryResult
     */
    public function find($id) : EntryResult
    {
        $entry = EntryModel::findOrFail($id);

        return new EntryResult(
            $entry->id,
            $entry->batch_id,
            $entry->type,
            $entry->content,
            $entry->created_at
        );
    }

    /**
     * Return all the entries of a given type.
     *
     * @param  string|null  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Support\Collection[\Laravel\Telescope\EntryResult]
     */
    public function get($type, EntryQueryOptions $options)
    {
        return EntryModel::withTelescopeOptions($type, $options)
            ->take($options->limit)
            ->orderByDesc('id')
            ->get()->map(function ($entry) {
                return new EntryResult(
                    $entry->id,
                    $entry->batch_id,
                    $entry->type,
                    $entry->content,
                    $entry->created_at
                );
            });
    }

    /**
     * Store the given array of entries.
     *
     * @param  \Illuminate\Support\Collection[\Laravel\Telescope\IncomingEntry]  $entries
     * @return void
     */
    public function store(Collection $entries)
    {
        $entries->each(function (IncomingEntry $entry) {
            $this->storeTags(
                EntryModel::forceCreate($entry->toArray())->id,
                $entry
            );
        });
    }

    /**
     * Store the tags for the given entry.
     *
     * @param  int  $entryId
     * @param  \Laravel\Telescope\IncomingEntry  $entry
     * @return void
     */
    protected function storeTags($entryId, IncomingEntry $entry)
    {
        DB::table('telescope_entries_tags')->insert(collect($entry->tags)->map(function ($tag) use ($entryId) {
            return [
                'entry_id' => $entryId,
                'tag' => $tag,
            ];
        })->toArray());
    }

    /**
     * Determine if any of the given tags are currently being monitored.
     *
     * @param  array  $tags
     * @return bool
     */
    public function isMonitoring(array $tags)
    {
        return count(array_intersect($tags, $this->monitoring())) > 0;
    }

    /**
     * Get the list of tags currently being monitored.
     *
     * @return array
     */
    public function monitoring()
    {
        return DB::table('telescope_monitoring')->pluck('tag')->all();
    }

    /**
     * Begin monitoring the given list of tags.
     *
     * @param  array  $tags
     * @return void
     */
    public function monitor(array $tags)
    {
        $tags = array_diff($tags, $this->monitoring());

        if (empty($tags)) {
            return;
        }

        DB::table('telescope_monitoring')->insert(collect($tags)->mapWithKeys(function ($tag) {
            return ['tag' => $tag];
        })->all());
    }

    /**
     * Stop monitoring the given list of tags.
     *
     * @param  array  $tags
     * @return void
     */
    public function stopMonitoring(array $tags)
    {
        DB::table('telescope_monitoring')->whereIn('tag', $tags)->delete();

        $entryIds = DB::table('telescope_entries_tags')
                    ->whereIn('tag', $tags)
                    ->pluck('entry_id')
                    ->unique()
                    ->all();

        DB::table('telescope_entries')
                    ->whereIn('entry_id', $entryIds)
                    ->delete();

        DB::table('telescope_entries_tags')
                    ->whereIn('entry_id', $entryIds)
                    ->delete();
    }
}
