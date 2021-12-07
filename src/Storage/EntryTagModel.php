<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Database\Eloquent\Model;

class EntryTagModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telescope_entries_tags';

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
    protected $primaryKey = null;

    /**
     * Prevent Eloquent from overriding uuid with `lastInsertId`.
     *
     * @var bool
     */
    public $incrementing = false;

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
