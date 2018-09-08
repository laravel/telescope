<?php

namespace Laravel\Telescope\Http\Controllers;

use Laravel\Telescope\EntryType;

class RedisController extends EntryController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType()
    {
        return EntryType::REDIS;
    }
}
