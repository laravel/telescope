<?php

namespace Laravel\Telescope\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;

class Authenticated implements Authenticatable
{
    public $email;

    public function getAuthIdentifierName()
    {
        return 'Telescope Test';
    }

    public function getAuthIdentifier()
    {
        return 'telescope-test';
    }

    public function getAuthPassword()
    {
        return 'secret';
    }

    public function getRememberToken()
    {
        return 'i-am-telescope';
    }

    public function setRememberToken($value)
    {
        //
    }

    public function getRememberTokenName()
    {
        //
    }
}
