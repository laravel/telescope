<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Database\Eloquent\Model;

class MonitoredTagModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telescope_monitoring';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
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
