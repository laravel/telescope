<?php

namespace Laravel\Telescope\Http\Controllers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Watchers\ClientRequestWatcher;

class ClientRequestController extends EntryController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType()
    {
        return EntryType::CLIENT_REQUEST;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher()
    {
        return ClientRequestWatcher::class;
    }
}
