<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Http\Events\RequestHandled;

class RequestsWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(RequestHandled::class, [$this, 'recordNewQuery']);
    }

    /**
     * Record a new query was executed.
     *
     * @param \Illuminate\Foundation\Http\Events\RequestHandled $event
     * @return void
     */
    public function recordNewQuery(RequestHandled $event)
    {
        Telescope::record(8, [
            'payload' => $event->request->all(),
            'uri' => str_replace($event->request->root(), '', $event->request->path()),
            'method' => $event->request->method(),
            'headers' => $this->formatHeaders($event->request->headers->all()),
            'response' => $this->formatResponse($event->response),
            'response_status' => $event->response->getStatusCode(),
        ]);
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
}