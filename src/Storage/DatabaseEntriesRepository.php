<?php

namespace Laravel\Telescope\Storage;

use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Contracts\ClearableRepository;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;
use Laravel\Telescope\Contracts\PrunableRepository;
use Laravel\Telescope\Contracts\TerminableRepository;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;

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
     * @param  int|null  $chunkSize
     * @return void
     */
    public function __construct(string $connection, ?int $chunkSize = null)
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
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            throw new \InvalidArgumentException('Invalid UUID format');
        }

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
     * @param  \Illuminate\Support\Collection<int, \Laravel\Telescope\IncomingEntry>  $entries
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
                $entry->content = json_encode($entry->content, JSON_INVALID_UTF8_SUBSTITUTE);

                return $entry->toArray();
            })->toArray());
        });

        $this->storeTags($entries->pluck('tags', 'uuid'));
    }

    /**
     * Store the given array of exception entries.
     *
     * @param  \Illuminate\Support\Collection<int, \Laravel\Telescope\IncomingEntry>  $exceptions
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
                        ->where('should_display_on_index', true)
                        ->update(['should_display_on_index' => false]);

                return array_merge($exception->toArray(), [
                    'family_hash' => $exception->familyHash(),
                    'content' => json_encode(
                        array_merge($exception->content, ['occurrences' => $occurrences + 1]),
                        JSON_INVALID_UTF8_SUBSTITUTE
                    ),
                ]);
            })->toArray());
        });

        $this->storeTags($exceptions->pluck('tags', 'uuid'));
    }

    /**
     * Store the tags for the given entries.
     *
     * @param  \Illuminate\Support\Collection<string, array<array-key, mixed>>  $results
     * @return void
     */
    protected function storeTags(Collection $results)
    {
        $toInsert = [];

        foreach ($results as $uuid => $tags) {
            foreach ($tags as $tag) {
                $toInsert[] = [
                    'entry_uuid' => $uuid,
                    'tag' => $tag,
                ];

                if (count($toInsert) >= $this->chunkSize) {
                    $this->insertChunkOfTags($toInsert);
                    $toInsert = [];
                }
            }
        }

        if ($toInsert !== []) {
            $this->insertChunkOfTags($toInsert);
        }
    }

    /**
     * Insert a chunk of tags, ignoring unique constraint violations.
     *
     * @param  array<int, array{entry_uuid: string, tag: string}>  $tags
     * @return void
     */
    protected function insertChunkOfTags($tags)
    {
        try {
            $this->table('telescope_entries_tags')->insert($tags);
        } catch (UniqueConstraintViolationException $e) {
            // Ignore tags that already exist...
        }
    }

    /**
     * Store the given entry updates and return the failed updates.
     *
     * @param  \Illuminate\Support\Collection|\Laravel\Telescope\EntryUpdate[]  $updates
     * @return \Illuminate\Support\Collection|null
     */
    public function update(Collection $updates)
    {
        $failedUpdates = [];

        foreach ($updates as $update) {
            $entry = $this->table('telescope_entries')
                            ->where('uuid', $update->uuid)
                            ->where('type', $update->type)
                            ->first();

            if (! $entry) {
                $failedUpdates[] = $update;

                continue;
            }

            $content = json_encode(array_merge(
                json_decode($entry->content ?? $entry['content'] ?? [], true) ?: [], $update->changes
            ));

            $this->table('telescope_entries')
                            ->where('uuid', $update->uuid)
                            ->where('type', $update->type)
                            ->update(['content' => $content]);

            $this->updateTags($update);
        }

        return collect($failedUpdates);
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
            try {
                $this->table('telescope_entries_tags')->insert(
                    collect($entry->tagsChanges['added'])->map(function ($tag) use ($entry) {
                        return [
                            'entry_uuid' => $entry->uuid,
                            'tag' => $tag,
                        ];
                    })->toArray()
                );
            } catch (UniqueConstraintViolationException $e) {
                // Ignore tags that already exist...
            }
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
     * @param  bool  $keepExceptions
     * @return int
     */
    public function prune(DateTimeInterface $before, $keepExceptions)
    {
        $query = $this->table('telescope_entries')
                ->where('created_at', '<', $before);

        if ($keepExceptions) {
            $query->where('type', '!=', 'exception');
        }

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
     * Note: This method does NOT delete security whitelist patterns or global security rules,
     * as these are configuration data that should persist even when clearing entries.
     *
     * @return void
     */
    public function clear()
    {
        do {
            $deleted = $this->table('telescope_entries')->take($this->chunkSize)->delete();
        } while ($deleted !== 0);

        do {
            $deleted = $this->table('telescope_monitoring')->take($this->chunkSize)->delete();
        } while ($deleted !== 0);

        // Security whitelist and global security rules are NOT deleted here
        // as they are configuration data, not entry data
    }

    /**
     * Get the list of security whitelist patterns.
     *
     * @return array
     */
    public function securityWhitelist()
    {
        return $this->table('telescope_security_whitelist')
            ->where('enabled', true)
            ->get()
            ->map(function ($pattern) {
                // Helper to decode JSON rules and ensure it's an associative array (object)
                $decodeRules = function ($json) {
                    if (empty($json)) {
                        return [];
                    }
                    $decoded = json_decode($json, true);
                    // If decode failed or is null, return empty array
                    if ($decoded === null) {
                        return [];
                    }
                    // If it's already an associative array (object), return it
                    if (is_array($decoded) && (empty($decoded) || array_keys($decoded) !== range(0, count($decoded) - 1))) {
                        return $decoded;
                    }
                    // Should never happen, but return empty array as fallback
                    return [];
                };

                return [
                    'id' => $pattern->id,
                    'name' => $pattern->name,
                    'path_pattern' => $pattern->path_pattern,
                    'method' => $pattern->method,
                    'path_params_rules' => $decodeRules($pattern->path_params_rules) ?: [],
                    'query_rules' => $decodeRules($pattern->query_rules) ?: [],
                    'payload_rules' => $decodeRules($pattern->payload_rules) ?: [],
                    'header_rules' => $decodeRules($pattern->header_rules) ?: [],
                    'is_regex' => (bool) $pattern->is_regex,
                    'enabled' => (bool) $pattern->enabled,
                ];
            })
            ->all();
    }

    /**
     * Add a pattern to the security whitelist.
     *
     * @param  array  $pattern
     * @return int
     */
    public function addSecurityWhitelist(array $pattern)
    {
        // Ensure rules are objects, not arrays
        $normalizeRules = function ($rules) {
            if (empty($rules)) {
                return '{}';
            }
            // If it's already an object/associative array, encode it
            if (is_array($rules) && (empty($rules) || array_keys($rules) !== range(0, count($rules) - 1))) {
                return json_encode($rules);
            }
            // If it's a numeric array, convert to object (shouldn't happen)
            return '{}';
        };

        $id = $this->table('telescope_security_whitelist')->insertGetId([
            'name' => $pattern['name'] ?? null,
            'path_pattern' => $pattern['path_pattern'],
            'method' => $pattern['method'] ?? null,
            'path_params_rules' => $normalizeRules($pattern['path_params_rules'] ?? []),
            'query_rules' => $normalizeRules($pattern['query_rules'] ?? []),
            'payload_rules' => $normalizeRules($pattern['payload_rules'] ?? []),
            'header_rules' => $normalizeRules($pattern['header_rules'] ?? []),
            'is_regex' => $pattern['is_regex'] ?? false,
            'enabled' => $pattern['enabled'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Update a security whitelist pattern.
     *
     * @param  int  $id
     * @param  array  $pattern
     * @return void
     */
    public function updateSecurityWhitelist(int $id, array $pattern)
    {
        // Ensure rules are objects, not arrays
        $normalizeRules = function ($rules) {
            if (empty($rules)) {
                return '{}';
            }
            // If it's already an object/associative array, encode it
            if (is_array($rules) && (empty($rules) || array_keys($rules) !== range(0, count($rules) - 1))) {
                return json_encode($rules);
            }
            // If it's a numeric array, convert to object (shouldn't happen)
            return '{}';
        };

        $this->table('telescope_security_whitelist')
            ->where('id', $id)
            ->update([
                'name' => $pattern['name'] ?? null,
                'path_pattern' => $pattern['path_pattern'],
                'method' => $pattern['method'] ?? null,
                'path_params_rules' => $normalizeRules($pattern['path_params_rules'] ?? []),
                'query_rules' => $normalizeRules($pattern['query_rules'] ?? []),
                'payload_rules' => $normalizeRules($pattern['payload_rules'] ?? []),
                'header_rules' => $normalizeRules($pattern['header_rules'] ?? []),
                'is_regex' => $pattern['is_regex'] ?? false,
                'enabled' => $pattern['enabled'] ?? true,
                'updated_at' => now(),
            ]);
    }

    /**
     * Remove a pattern from the security whitelist.
     *
     * @param  int  $id
     * @return void
     */
    public function removeSecurityWhitelist(int $id)
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid ID: must be positive integer');
        }

        $deleted = $this->table('telescope_security_whitelist')
            ->where('id', $id)
            ->delete();

        if (! $deleted) {
            throw new \RuntimeException('Whitelist pattern not found or already deleted');
        }
    }

    /**
     * Get all global security rules.
     *
     * @return array
     */
    public function globalSecurityRules()
    {
        return $this->table('telescope_global_security_rules')
            ->where('enabled', true)
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'category' => $rule->category,
                    'description' => $rule->description,
                    'patterns' => json_decode($rule->patterns, true) ?? [],
                    'exclude_paths' => json_decode($rule->exclude_paths, true) ?? [],
                    'enabled' => (bool) $rule->enabled,
                ];
            })
            ->all();
    }

    /**
     * Add a global security rule.
     *
     * @param  array  $rule
     * @return int
     */
    public function addGlobalSecurityRule(array $rule)
    {
        $id = $this->table('telescope_global_security_rules')->insertGetId([
            'name' => $rule['name'],
            'category' => $rule['category'],
            'description' => $rule['description'] ?? null,
            'patterns' => json_encode($rule['patterns'] ?? []),
            'exclude_paths' => json_encode($rule['exclude_paths'] ?? []),
            'enabled' => $rule['enabled'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Update a global security rule.
     *
     * @param  int  $id
     * @param  array  $rule
     * @return void
     */
    public function updateGlobalSecurityRule(int $id, array $rule)
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid ID: must be positive integer');
        }
        $this->table('telescope_global_security_rules')
            ->where('id', $id)
            ->update([
                'name' => $rule['name'],
                'category' => $rule['category'],
                'description' => $rule['description'] ?? null,
                'patterns' => json_encode($rule['patterns'] ?? []),
                'exclude_paths' => json_encode($rule['exclude_paths'] ?? []),
                'enabled' => $rule['enabled'] ?? true,
                'updated_at' => now(),
            ]);
    }

    /**
     * Remove a global security rule.
     *
     * @param  int  $id
     * @return void
     */
    public function removeGlobalSecurityRule(int $id)
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid ID: must be positive integer');
        }

        $deleted = $this->table('telescope_global_security_rules')
            ->where('id', $id)
            ->delete();

        if (! $deleted) {
            throw new \RuntimeException('Global security rule not found or already deleted');
        }
    }

    /**
     * Initialize default global security rules.
     *
     * @return void
     */
    public function initializeDefaultGlobalSecurityRules()
    {
        $defaults = \Laravel\Telescope\Security\GlobalSecurityValidator::getDefaultPatterns();

        foreach ($defaults as $rule) {
            try {
                $this->addGlobalSecurityRule($rule);
            } catch (UniqueConstraintViolationException $e) {
                // Rule might already exist, skip
            }
        }
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
