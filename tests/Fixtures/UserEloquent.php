<?php

namespace Laravel\Telescope\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class UserEloquent extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
