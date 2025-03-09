<?php

namespace Laravel\Telescope\Watchers;

use Closure;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use ReflectionFunction;

class ViewWatcher extends Watcher
{
    use FormatsClosure;

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

        /** @var ViewContract $view */
        $view = $data[0];

        if ($view instanceof View) {
            Telescope::recordView(IncomingEntry::make(array_filter([
                'name' => $view->getName(),
                'path' => $this->extractPath($view),
                'data' => $this->extractKeysFromData($view),
                'composers' => $this->formatComposers($view),
            ])));
        }
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

    /**
     * Format list of view composers and view creators.
     *
     * @param  \Illuminate\View\View  $view
     * @return array
     */
    protected function formatComposers($view)
    {
        $name = $view->getName();

        return collect([
            'composing: '.$name,
            'creating: '.$name,
        ])->map(function ($event) {
            return $this->getComposersForEvent($event)
                ->map(function ($composer) use ($event) {
                    return [
                        'name' => $composer,
                        'type' => Str::startsWith($event, 'creating:') ? 'creator' : 'composer',
                    ];
                });
        })->collapse()->values()->toArray();
    }

    /**
     * Get all view composers for the given event.
     *
     * @param  string  $eventName
     * @return \Illuminate\Support\Collection
     */
    protected function getComposersForEvent($eventName)
    {
        return collect(app('events')->getListeners($eventName))
            ->map(function ($listener) {
                return (new ReflectionFunction($listener))->getStaticVariables();
            })->reject(function ($variables) {
                if (is_array($variables['listener'])) {
                    return Str::contains(get_class($variables['listener'][0]), 'Laravel\\Telescope');
                }

                return ! $variables['listener'] instanceof Closure;
            })->map(function ($variables) {
                if (is_array($variables['listener'])) {
                    return;
                }

                $closure = new ReflectionFunction($listener = $variables['listener']);

                if ($this->isWildcardViewComposer($variables, $closure)) {
                    $closure = new ReflectionFunction($listener = $closure->getStaticVariables()['callback']);
                }

                if ($this->isViewComposerClosure($closure)) {
                    return $closure->getStaticVariables()['class'].'@'.$closure->getStaticVariables()['method'];
                }

                return $this->formatClosureListener($listener);
            })->filter();
    }

    /**
     * Determine if the view composer is a wildcard composer.
     *
     * @param  array  $variables
     * @param  \ReflectionFunction  $closure
     * @return bool
     */
    protected function isWildcardViewComposer(array $variables, ReflectionFunction $closure): bool
    {
        return $variables['wildcard'] && array_key_exists('callback', $closure->getStaticVariables());
    }

    /**
     * Check if the given closure is a view composer class.
     *
     * Wildcard view composers are wrapped in an extra closure.
     *
     * @param  \ReflectionFunction  $closure
     * @return bool
     */
    protected function isViewComposerClosure(ReflectionFunction $closure): bool
    {
        return $closure->getClosureScopeClass()->implementsInterface(Factory::class) &&
               array_key_exists('class', $closure->getStaticVariables());
    }
}
