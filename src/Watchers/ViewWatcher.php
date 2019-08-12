<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\View\View;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;

class ViewWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen($this->options['events'] ?? 'composing:*', [$this, 'recordAction']);
    }

    /**
     * Record an action.
     *
     * @param  string  $event
     * @param  array  $data
     * @return void
     */
    public function recordAction($event, $data)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        /** @var View $view */
        $view = $data[0];

        Telescope::recordView(IncomingEntry::make(array_filter([
            'name' => $view->getName(),
            'path' => $view->getPath(),
            'data' => array_keys($view->getData()),
        ])));
    }
}
