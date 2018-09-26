<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class EntryModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telescope_entries';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Scope the query for the given query options.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithTelescopeOptions($query, $type, EntryQueryOptions $options)
    {
        $this->whereType($query, $type)
                ->whereBatchId($query, $options)
                ->whereTag($query, $options)
                ->whereGroup($query, $options)
                ->whereBeforeSequence($query, $options);

        return $query;
    }

    /**
     * Scope the query for the given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return $this
     */
    protected function whereType($query, $type)
    {
        $query->when($type, function ($query, $type) {
            return $query->where('type', $type);
        });

        return $this;
    }

    /**
     * Scope the query for the given batch ID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return $this
     */
    protected function whereBatchId($query, EntryQueryOptions $options)
    {
        $query->when($options->batchId, function ($query, $batchId) {
            return $query->where('batch_id', $batchId);
        });

        return $this;
    }

    /**
     * Scope the query for the given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return $this
     */
    protected function whereTag($query, EntryQueryOptions $options)
    {
        $query->when($options->tag, function ($query, $tag) {
            return $query->whereIn('uuid', DB::table('telescope_entries_tags')
                        ->whereTag($tag)
                        ->pluck('entry_uuid')
                        ->toArray());
        });

        return $this;
    }

    /**
     * Scope the query for the given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return $this
     */
    protected function whereGroup($query, EntryQueryOptions $options)
    {
        $query->when($options->group, function ($query, $group) {
            return $query->where('group', $group);
        });

        return $this;
    }

    /**
     * Scope the query for the given pagination options.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return $this
     */
    protected function whereBeforeSequence($query, EntryQueryOptions $options)
    {
        $query->when($options->beforeSequence, function ($query, $beforeSequence) {
            return $query->where('sequence', '<', $beforeSequence);
        });

        return $this;
    }

    /**
     * Scope the query for grouping.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroupOccurrences($query, $type, EntryQueryOptions $options)
    {
        if ($options->group || $type != 'exception') {
            return;
        }

        return $query->groupBy(DB::raw('`group`'))
            ->select(
                DB::raw('MAX(uuid) as uuid'),
                DB::raw('MAX(batch_id) as batch_id'),
                DB::raw('`group` as content'),
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('MAX(sequence) as sequence'),
                DB::raw('COUNT(sequence) as occurrences')
            );
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return config('telescope.storage.database.connection');
    }
}
