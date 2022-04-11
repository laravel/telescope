<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Telescope\FormatModel;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class GateWatcher extends Watcher
{
    use FetchesStackTrace;

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(GateEvaluated::class, [$this, 'handleGateEvaluated']);
    }

    /**
     * Handle the GateEvaluated event.
     *
     * @param  \Illuminate\Auth\Access\Events\GateEvaluated  $event
     * @return void
     */
    public function handleGateEvaluated(GateEvaluated $event)
    {
        $this->recordGateCheck($event->user, $event->ability, $event->result, $event->arguments);
    }

    /**
     * Record a gate check.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed|null  $user
     * @param  string  $ability
     * @param  bool  $result
     * @param  array  $arguments
     * @return bool
     */
    public function recordGateCheck($user, $ability, $result, $arguments)
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($ability)) {
            return;
        }

        $caller = $this->getCallerFromStackTrace([0, 1]);

        Telescope::recordGate(IncomingEntry::make([
            'ability' => $ability,
            'result' => $this->gateResult($result),
            'arguments' => $this->formatArguments($arguments),
            'file' => $caller['file'] ?? null,
            'line' => $caller['line'] ?? null,
        ]));

        return $result;
    }

    /**
     * Determine if the ability should be ignored.
     *
     * @param  string  $ability
     * @return bool
     */
    private function shouldIgnore($ability)
    {
        return Str::is($this->options['ignore_abilities'] ?? [], $ability);
    }

    /**
     * Determine if the gate result is denied or allowed.
     *
     * @param  bool|\Illuminate\Auth\Access\Response  $result
     * @return string
     */
    private function gateResult($result)
    {
        if ($result instanceof Response) {
            return $result->allowed() ? 'allowed' : 'denied';
        }

        return $result ? 'allowed' : 'denied';
    }

    /**
     * Format the given arguments.
     *
     * @param  array  $arguments
     * @return array
     */
    private function formatArguments($arguments)
    {
        return collect($arguments)->map(function ($argument) {
            return $argument instanceof Model ? FormatModel::given($argument) : $argument;
        })->toArray();
    }
}
