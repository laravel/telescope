<?php

namespace Laravel\Telescope\Storage;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\Contracts\PrunableRepository;
use Laravel\Telescope\Contracts\TerminableRepository;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;

class DatabaseEntriesRepository implements Contract, PrunableRepository, TerminableRepository
{
    /**
     * The database connection name that should be used.
     *
     * @var string
     */
    protected $connection;

    /**
     * The tags currently being monitored.
     *
     * @var array|null
     */
    protected $monitoredTags;

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
        $entry = EntryModel::on($this->connection)->whereUuid($id)->first();

        $tags = $this->table('telescope_entries_tags')
                        ->where('entry_uuid', $id)
                        ->pluck('tag')
                        ->all();

        return new EntryResult(
            $entry->uuid,
            null,
            $entry->batch_id,
            $entry->type,
            $entry->content,
            $entry->created_at,
            $tags,
            1,
            $entry->group
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
            ->groupOccurrences($type, $options)
            ->take($options->limit)
            ->orderByDesc('sequence')
            ->get()->map(function ($entry) {
                return new EntryResult(
                    $entry->uuid,
                    $entry->sequence,
                    $entry->batch_id,
                    $entry->type,
                    $entry->content,
                    $entry->created_at,
                    [],
                    $entry->occurrences
                );
            });
    }

    /**
     * Return all the entries of a given type and group.
     *
     * @param  string  $type
     * @param  string  $group
     * @return int
     */
    public function countOccurrences($type, $group)
    {
        return EntryModel::on($this->connection)
            ->where('type', $type)
            ->where('group', $group)
            ->count('sequence');
    }

    /**
     * Store the given array of entries.
     *
     * @param  \Illuminate\Support\Collection[\Laravel\Telescope\IncomingEntry]  $entries
     * @return void
     */
    public function store(Collection $entries)
    {
        $this->table('telescope_entries')->insert($entries->map(function ($entry) {
            $entry->content = json_encode($entry->content);

            return $entry->toArray();
        })->toArray());

        $this->storeTags($entries->pluck('tags', 'uuid'));
    }

    /**
     * Store the tags for the given entries.
     *
     * @param  \Illuminate\Support\Collection  $results
     * @return void
     */
    protected function storeTags($results)
    {
        $this->table('telescope_entries_tags')->insert($results->flatMap(function ($tags, $uuid) {
            return collect($tags)->map(function ($tag) use ($uuid) {
                return [
                    'entry_uuid' => $uuid,
                    'tag' => $tag,
                ];
            });
        })->all());
    }

    /**
     * Determine if any of the given tags are currently being monitored.
     *
     * @param  array  $tags
     * @return bool
     */
    public function isMonitoring(array $tags)
    {
        if (is_null($this->monitoredTags)) {
            $this->monitoredTags = $this->monitoring();
        }

        return count(array_intersect($tags, $this->monitoredTags)) > 0;
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
     * Perform any clean-up tasks needed after storing Telescope entries.
     *
     * @return void
     */
    public function terminate()
    {
        $this->monitoredTags = null;
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
