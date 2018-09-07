<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Support\Arr;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Http\Events\RequestHandled;

class RequestsWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(RequestHandled::class, [$this, 'recordRequest']);
    }

    /**
     * Record a new query was executed.
     *
     * @param \Illuminate\Foundation\Http\Events\RequestHandled $event
     * @return void
     */
    public function recordRequest(RequestHandled $event)
    {
        Telescope::recordRequest(IncomingEntry::make([
            'payload' => $this->formatPayload($event->request->all()),
            'uri' => str_replace($event->request->root(), '', $event->request->path()),
            'method' => $event->request->method(),
            'headers' => $this->formatHeaders($event->request->headers->all()),
            'response' => $this->formatResponse($event->response),
            'response_status' => $event->response->getStatusCode(),
        ]));
    }

    /**
     * Format the given payload.
     *
     * @param  array $payload
     * @return array
     */
    private function formatPayload($payload)
    {
        foreach (Telescope::$protectedRequestParameters as $parameter) {
            if (Arr::get($payload, $parameter)) {
                Arr::set($payload, $parameter, '*****');
            }
        }

        return $payload;
    }

    /**
     * Format the given headers.
     *
     * @param  array $headers
     * @return array
     */
    private function formatHeaders($headers)
    {
        return collect($headers)->map(function ($header) {
            return $header[0];
        })->toArray();
    }

    /**
     * Format the given response object.
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @return array
     */
    private function formatResponse(Response $response)
    {
        if (is_string($response->getContent()) &&
            is_array(json_decode($response->getContent(), true)) &&
            json_last_error() == JSON_ERROR_NONE
        ) {
            return json_decode($response->getContent(), true);
        }

        return "HTML Response";
    }
}
