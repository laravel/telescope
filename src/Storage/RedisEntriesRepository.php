<?php

namespace Laravel\Telescope\Storage;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Contracts\PrunableRepository;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;
use Illuminate\Contracts\Redis\Factory as Redis;

class RedisEntriesRepository implements Contract, PrunableRepository
{
    /**
     * The redis connection name that should be used.
     *
     * @var string
     */
    protected $connection;

    /**
     * @var \Illuminate\Contracts\Redis\Factory
     */
    private $redis;

    /**
     * Create a new redis repository.
     *
     * @param  string  $connectionName
     * @return void
     */
    public function __construct(string $connection, Redis $redis)
    {
        $this->connection = $connection;
        $this->redis = $redis->connection($connection);
    }

    /**
     * Find the entry with the given ID.
     *
     * @param  mixed  $id
     * @return \Laravel\Telescope\EntryResult
     */
    public function find($id) : EntryResult
    {
        $entry = $this->redis->hgetall('telescope:'.$id);

        abort_if(empty($entry), 404);

        return new EntryResult(
            $entry['uuid'],
            null,
            $entry['batch_id'],
            $entry['type'],
            json_decode($entry['content'], true),
            Carbon::createFromFormat('Y-m-d H:i:s', $entry['created_at'])
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
        $ids = $this->getEntriesIds($type, $options);

        return collect($this->redis->pipeline(function ($pipe) use ($ids) {
            foreach ($ids as $id => $sequence) {
                $pipe->hgetAll('telescope:'.(is_numeric($id) ? $sequence : $id));
            }
        }))->map(function ($entry) use ($ids) {
            return new EntryResult(
                $entry['uuid'],
                $ids[$entry['uuid']] ?? null,
                $entry['batch_id'],
                $entry['type'],
                json_decode($entry['content'], true),
                Carbon::createFromFormat('Y-m-d H:i:s', $entry['created_at'])
            );
        });
    }

    /**
     * Return all the entries IDs of a given type.
     *
     * @param  string|null  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Support\Collection[\Laravel\Telescope\EntryResult]
     */
    private function getEntriesIds($type, EntryQueryOptions $options)
    {
        $beforeSequence = $options->beforeSequence ? $options->beforeSequence : '+inf';

        $offset = $options->beforeSequence ? 1 : 0;

        if (! $type) {
            return $this->redis->smembers('telescope:batch:'.$options->batchId);
        }

        if (! $options->tag) {
            return $this->redis->zrevrangebyscore('telescope:type:'.$type, $beforeSequence, '-inf', [
                'withscores' => true, 'limit' => [$offset, $options->limit]
            ]);
        }

        $pipe = $this->redis->pipeline();

        $pipe->zinterstore('telescope:_temp:'.$type.':'.$options->tag, 2,
            'telescope:type:'.$type, 'telescope:tag:'.$options->tag,
            ['aggregate' => 'max']
        );

        $pipe->expire('telescope:_temp:'.$type.':'.$options->tag, 30);

        $pipe->zrevrangebyscore('telescope:_temp:'.$type.':'.$options->tag, $beforeSequence, '-inf', [
            'withscores' => true, 'limit' => [$offset, $options->limit]
        ]);

        return $pipe->execute()[2];
    }

    /**
     * Store the given array of entries.
     *
     * @param  \Illuminate\Support\Collection[\Laravel\Telescope\IncomingEntry]  $entries
     * @return void
     */
    public function store(Collection $entries)
    {
        $pipe = $this->redis->pipeline();

        $entries->each(function (IncomingEntry $entry) use (&$pipe) {
            $entry->content = json_encode($entry->content);

            $score = str_replace(',', '.', microtime(true));

            $pipe->hmset('telescope:'.$entry->uuid, $entry->toArray() + ['tags' => json_encode($entry->tags)]);
            $pipe->expire('telescope:'.$entry->uuid, config('telescope.storage.redis.lifetime'));

            $pipe->zadd('telescope:type:'.$entry->type, $score, $entry->uuid);
            $pipe->expire('telescope:type:'.$entry->type, config('telescope.storage.redis.lifetime'));

            foreach($entry->tags as $tag){
                $pipe->zadd('telescope:tag:'.$tag, $score, $entry->uuid);
                $pipe->expire('telescope:tag:'.$tag, config('telescope.storage.redis.lifetime'));
            }

            $pipe->sadd('telescope:batch:'.$entry->batchId, $entry->uuid);
            $pipe->expire('telescope:batch:'.$entry->batchId, config('telescope.storage.redis.lifetime'));
        });

        $pipe->execute();
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
