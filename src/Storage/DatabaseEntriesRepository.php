<?php

namespace Laravel\Telescope\Storage;

use DateTimeInterface;
use Laravel\Telescope\EntryType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Contracts\PrunableRepository;
use Laravel\Telescope\Contracts\ClearableRepository;
use Laravel\Telescope\Contracts\TerminableRepository;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;

class DatabaseEntriesRepository implements Contract, ClearableRepository, PrunableRepository, TerminableRepository
{
    /**
     * The database connection name that should be used.
     *
     * @var string
     */
    protected $connection;

    /**
     * The number of entries that will be inserted at once into the database.
     *
     * @var int
     */
    protected $chunkSize = 1000;

    /**
     * The tags currently being monitored.
     *
     * @var array|null
     */
    protected $monitoredTags;

    /**
     * Create a new database repository.
     *
     * @param  string  $connection
     * @param  int     $chunkSize
     * @return void
     */
    public function __construct(string $connection, int $chunkSize = null)
    {
        $this->connection = $connection;

        if ($chunkSize) {
            $this->chunkSize = $chunkSize;
        }
    }

    /**
     * Find the entry with the given ID.
     *
     * @param  mixed  $id
     * @return \Laravel\Telescope\EntryResult
     */
    public function find($id): EntryResult
    {
        $entry = EntryModel::on($this->connection)->whereUuid($id)->firstOrFail();

        $tags = $this->table('telescope_entries_tags')
                        ->where('entry_uuid', $id)
                        ->pluck('tag')
                        ->all();

        return new EntryResult(
            $entry->uuid,
            null,
            $entry->batch_id,
            $entry->type,
            $entry->family_hash,
            $entry->content,
            $entry->created_at,
            $tags
        );
    }

    /**
     * Return all the entries of a given type.
     *
     * @param  string|null  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Support\Collection|\Laravel\Telescope\EntryResult[]
     */
    public function get($type, EntryQueryOptions $options)
    {
        return EntryModel::on($this->connection)
            ->withTelescopeOptions($type, $options)
            ->take($options->limit)
            ->orderByDesc('sequence')
            ->get()->reject(function ($entry) {
                return ! is_array($entry->content);
            })->map(function ($entry) {
                return new EntryResult(
                    $entry->uuid,
                    $entry->sequence,
                    $entry->batch_id,
                    $entry->type,
                    $entry->family_hash,
                    $entry->content,
                    $entry->created_at,
                    []
                );
            })->values();
    }

    /**
     * Counts the occurences of an exception.
     *
     * @param  \Laravel\Telescope\IncomingEntry  $exception
     * @return int
     */
    protected function countExceptionOccurences(IncomingEntry $exception)
    {
        return $this->table('telescope_entries')
                    ->where('type', EntryType::EXCEPTION)
                    ->where('family_hash', $exception->familyHash())
                    ->count();
    }

    /**
     * Store the given array of entries.
     *
     * @param  \Illuminate\Support\Collection|\Laravel\Telescope\IncomingEntry[]  $entries
     * @return void
     */
    public function store(Collection $entries)
    {
        if ($entries->isEmpty()) {
            return;
        }

        [$exceptions, $entries] = $entries->partition->isException();

        $this->storeExceptions($exceptions);

        $table = $this->table('telescope_entries');

        $entries->chunk($this->chunkSize)->each(function ($chunked) use ($table) {
            $table->insert($chunked->map(function ($entry) {
                $entry->content = json_encode($entry->content);

                return $entry->toArray();
            })->toArray());
        });

        $this->storeTags($entries->pluck('tags', 'uuid'));
    }

    /**
     * Store the given array of exception entries.
     *
     * @param  \Illuminate\Support\Collection|\Laravel\Telescope\IncomingEntry[]  $exceptions
     * @return void
     */
    protected function storeExceptions(Collection $exceptions)
    {
        $exceptions->chunk($this->chunkSize)->each(function ($chunked) {
            $this->table('telescope_entries')->insert($chunked->map(function ($exception) {
                $occurrences = $this->countExceptionOccurences($exception);

                $this->table('telescope_entries')
                        ->where('type', EntryType::EXCEPTION)
                        ->where('family_hash', $exception->familyHash())
                        ->update(['should_display_on_index' => false]);

                return array_merge($exception->toArray(), [
                    'family_hash' => $exception->familyHash(),
                    'content' => json_encode(array_merge(
                        $exception->content, ['occurrences' => $occurrences + 1]
                    )),
                ]);
            })->toArray());
        });

        $this->storeTags($exceptions->pluck('tags', 'uuid'));
    }

    /**
     * Store the tags for the given entries.
     *
     * @param  \Illuminate\Support\Collection  $results
     * @return void
     */
    protected function storeTags(Collection $results)
    {
        $results->chunk($this->chunkSize)->each(function ($chunked) {
            $this->table('telescope_entries_tags')->insert($chunked->flatMap(function ($tags, $uuid) {
                return collect($tags)->map(function ($tag) use ($uuid) {
                    return [
                        'entry_uuid' => $uuid,
                        'tag' => $tag,
                    ];
                });
            })->all());
        });
    }

    /**
     * Store the given entry updates.
     *
     * @param  \Illuminate\Support\Collection|\Laravel\Telescope\EntryUpdate[]  $updates
     * @return void
     */
    public function update(Collection $updates)
    {
        foreach ($updates as $update) {
            $entry = $this->table('telescope_entries')
                            ->where('uuid', $update->uuid)
                            ->where('type', $update->type)
                            ->first();

            if (! $entry) {
                continue;
            }

            $content = json_encode(array_merge(
                json_decode($entry->content, true) ?: [], $update->changes
            ));

            $this->table('telescope_entries')
                            ->where('uuid', $update->uuid)
                            ->where('type', $update->type)
                            ->update(['content' => $content]);

            $this->updateTags($update);
        }
    }

    /**
     * Update tags of the given entry.
     *
     * @param  \Laravel\Telescope\EntryUpdate  $entry
     * @return void
     */
    protected function updateTags($entry)
    {
        if (! empty($entry->tagsChanges['added'])) {
            $this->table('telescope_entries_tags')->insert(
                collect($entry->tagsChanges['added'])->map(function ($tag) use ($entry) {
                    return [
                        'entry_uuid' => $entry->uuid,
                        'tag' => $tag,
                    ];
                })->toArray()
            );
        }

        collect($entry->tagsChanges['removed'])->each(function ($tag) use ($entry) {
            $this->table('telescope_entries_tags')->where([
                'entry_uuid' => $entry->uuid,
                'tag' => $tag,
            ])->delete();
        });
    }

    /**
     * Load the monitored tags from storage.
     *
     * @return void
     */
    public function loadMonitoredTags()
    {
        try {
            $this->monitoredTags = $this->monitoring();
        } catch (\Throwable $e) {
            $this->monitoredTags = [];
        }
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
            $this->loadMonitoredTags();
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
     * @return int
     */
    public function prune(DateTimeInterface $before)
    {
        $query = $this->table('telescope_entries')
                ->where('created_at', '<', $before);

        $totalDeleted = 0;

        do {
            $deleted = $query->take($this->chunkSize)->delete();

            $totalDeleted += $deleted;
        } while ($deleted !== 0);

        return $totalDeleted;
    }

    /**
     * Clear all the entries.
     *
     * @return void
     */
    public function clear()
    {
        $this->table('telescope_entries')->delete();
        $this->table('telescope_monitoring')->delete();
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
