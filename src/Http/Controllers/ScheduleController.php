<?php

namespace Laravel\Telescope\Http\Controllers;

use Laravel\Telescope\EntryType;
use Illuminate\Routing\Controller;

class ScheduleController extends EntryController
{
    /**
     * The entry type for the controller.
     *
     * @return int
     */
    protected function entryType()
    {
        return EntryType::SCHEDULED_COMMAND;
    }
}
