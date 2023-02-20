<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Database\Eloquent\Model;
use Laravel\Telescope\Database\Factories\EntryModelFactory;

class EntryTagModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telescope_entries_tags';

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
    protected $primaryKey = '';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = '';

    /**
     * Prevent Eloquent from overriding uuid with `lastInsertId`.
     *
     * @var bool
     */
    public $incrementing = false;
}
