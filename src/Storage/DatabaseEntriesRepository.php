<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\EntryQueryOptions;
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
     * @param  int  $type
     * @param  \Laravel\Telescope\EntryQueryOptions  $options
     * @return \Illuminate\Support\Collection[\Laravel\Telescope\EntryResult]
     */
    public function get($type, EntryQueryOptions $options)
    {
        return EntryModel::telescopeOptions($type, $options)
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
}
