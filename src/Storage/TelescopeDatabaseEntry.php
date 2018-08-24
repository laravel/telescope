<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Database\Eloquent\Model;

class TelescopeDatabaseEntry extends Model
{
    protected $table = 'telescope_entries';
    protected $guarded = ['id'];
    protected $hidden = [];
    protected $appends = [];
    protected $dates = ['created_at'];
    public $timestamps = false;
    protected $casts = [
        'id' => 'integer',
        'type' => 'integer',
        'content' => 'json',
    ];
}
