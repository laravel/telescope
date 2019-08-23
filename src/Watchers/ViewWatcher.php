<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\View\View;
use Illuminate\Support\Str;
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
            'path' => $this->extractPath($view),
            'data' => $this->extractKeysFromData($view),
        ])));
    }

    /**
     * Extract the path from the given view.
     *
     * @param  \Illuminate\View\View  $view
     * @return string
     */
    protected function extractPath($view)
    {
        $path = $view->getPath();

        if (Str::startsWith($path, base_path())) {
            $path = substr($path, strlen(base_path()));
        }

        return $path;
    }

    /**
     * Extract the keys from the given view in array form.
     *
     * @param  \Illuminate\View\View  $view
     * @return array
     */
    protected function extractKeysFromData($view)
    {
        return collect($view->getData())->filter(function ($value, $key) {
            return ! in_array($key, ['app', '__env', 'obLevel', 'errors']);
        })->keys();
    }
}
