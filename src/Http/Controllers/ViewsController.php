<?php

namespace Laravel\Telescope\Http\Controllers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Watchers\ViewWatcher;

class ViewsController extends EntryController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType()
    {
        return EntryType::VIEW;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher()
    {
        return ViewWatcher::class;
    }
}
