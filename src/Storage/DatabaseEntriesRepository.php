<?php

namespace Laravel\Telescope\Storage;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Laravel\Telescope\Contracts\PrunableRepository;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;

class DatabaseEntriesRepository implements Contract, PrunableRepository
{
    /**
     * The database connection name that should be used.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database repository.
     *
     * @param  string  $connectionName
     * @return void
     */
    public function __construct(string $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Find the entry with the given ID.
     *
     * @param  mixed  $id
     * @return \Laravel\Telescope\EntryResult
     */
    public function find($id) : EntryResult
    {
        $entry = EntryModel::on($this->connection)->findOrFail($id);

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
        return EntryModel::on($this->connection)
            ->withTelescopeOptions($type, $options)
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
                EntryModel::on($this->connection)
                    ->forceCreate($entry->toArray())->id,
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
        $this->table('telescope_entries_tags')
                    ->insert(collect($entry->tags)
                    ->map(function ($tag) use ($entryId) {
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
        return $this->table('telescope_monitoring')->pluck('tag')->all();
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

        $this->table('telescope_monitoring')
                    ->insert(collect($tags)
                    ->mapWithKeys(function ($tag) {
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
        $this->table('telescope_monitoring')->whereIn('tag', $tags)->delete();
    }

    /**
     * Prune all of the entries older than the given date.
     *
     * @param  \DateTimeInterface  $before
     * @return void
     */
    public function prune(DateTimeInterface $before)
    {
        $this->table('telescope_entries')
                ->where('created_at', '<', $before)
                ->delete();
    }

    /**
     * Get a query builder instance for the given table.
     *
     * @param  string  $table
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table($table)
    {
        return DB::connection($this->connection)->table($table);
    }
}
