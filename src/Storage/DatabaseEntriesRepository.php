<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Carbon;
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
     * @param  int  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Support\Collection[\Laravel\Telescope\EntryResult]
     */
    public function get($type, EntryQueryOptions $options = null)
    {
        $options = $options ?: new EntryQueryOptions;

        $this->scopeForType($query = DB::table('telescope_entries'), $type)
                ->scopeForBatch($query, $options)
                ->scopeForTag($query, $options)
                ->scopeForPagination($query, $options);

        return $query
            ->take($options->limit)
            ->orderByDesc('id')
            ->get()->map(function ($entry) {
                return new EntryResult(
                    $entry->id,
                    $entry->batch_id,
                    $entry->type,
                    json_decode($entry->content, true),
                    Carbon::parse($entry->created_at)
                );
            });
    }

    /**
     * Scope the query for the given type.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  int  $type
     * @return $this
     */
    protected function scopeForType($query, $type)
    {
        $query->when($type, function ($query, $type) {
            return $query->where('type', $type);
        });

        return $this;
    }

    /**
     * Scope the query for the given batch ID.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return $this
     */
    protected function scopeForBatch($query, EntryQueryOptions $options)
    {
        $query->when($options->batchId, function ($query, $batchId) {
            return $query->where('batch_id', $batchId);
        });

        return $this;
    }

    /**
     * Scope the query for the given type.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return $this
     */
    protected function scopeForTag($query, EntryQueryOptions $options)
    {
        $query->when($options->tag, function ($query, $tag) {
            return $query->whereIn('id', DB::table('telescope_entries_tags')
                        ->whereTag($tag)
                        ->pluck('entry_id')
                        ->toArray());
        });

        return $this;
    }

    /**
     * Scope the query for the given pagination options.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return $this
     */
    protected function scopeForPagination($query, EntryQueryOptions $options)
    {
        $query->when($options->beforeId, function ($query, $beforeId) {
            return $query->where('id', '<', $beforeId);
        });

        return $this;
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
