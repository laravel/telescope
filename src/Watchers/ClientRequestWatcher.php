<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Symfony\Component\HttpFoundation\File\File;

class ClientRequestWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(ConnectionFailed::class, [$this, 'recordFailedRequest']);
        $app['events']->listen(ResponseReceived::class, [$this, 'recordResponse']);
    }

    /**
     * Record a HTTP Client connection failed request event.
     *
     * @param  \Illuminate\Http\Client\Events\ConnectionFailed  $event
     * @return void
     */
    public function recordFailedRequest(ConnectionFailed $event)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        Telescope::recordClientRequest(
            IncomingEntry::make([
                'method' => $event->request->method(),
                'uri' => $event->request->url(),
                'headers' => $this->headers($event->request->headers()),
                'payload' => $this->payload($this->input($event->request)),
            ])
            ->tags([$event->request->toPsrRequest()->getUri()->getHost()])
        );
    }

    /**
     * Record a HTTP Client response.
     *
     * @param  \Illuminate\Http\Client\Events\ResponseReceived  $event
     * @return void
     */
    public function recordResponse(ResponseReceived $event)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        Telescope::recordClientRequest(
            IncomingEntry::make([
                'method' => $event->request->method(),
                'uri' => $event->request->url(),
                'headers' => $this->headers($event->request->headers()),
                'payload' => $this->payload($this->input($event->request)),
                'response_status' => $event->response->status(),
                'response_headers' => $this->headers($event->response->headers()),
                'response' => $this->response($event->response),
                'duration' => $this->duration($event->response),
            ])
            ->tags([$event->request->toPsrRequest()->getUri()->getHost()])
        );
    }

    /**
     * Determine if the content is within the set limits.
     *
     * @param  string  $content
     * @return bool
     */
    public function contentWithinLimits($content)
    {
        $limit = $this->options['size_limit'] ?? 64;

        return mb_strlen($content) / 1000 <= $limit;
    }

    /**
     * Format the given response object.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return array|string
     */
    protected function response(Response $response)
    {
        $content = $response->body();

        $stream = $response->toPsrResponse()->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        if (is_string($content)) {
            if (is_array(json_decode($content, true)) &&
                json_last_error() === JSON_ERROR_NONE) {
                return $this->contentWithinLimits($content)
                        ? $this->hideParameters(json_decode($content, true), Telescope::$hiddenResponseParameters)
                        : 'Purged By Telescope';
            }

            if (Str::startsWith(strtolower($response->header('Content-Type') ?? ''), 'text/plain')) {
                return $this->contentWithinLimits($content) ? $content : 'Purged By Telescope';
            }
        }

        if ($response->redirect()) {
            return 'Redirected to '.$response->header('Location');
        }

        if (empty($content)) {
            return 'Empty Response';
        }

        return 'HTML Response';
    }

    /**
     * Format the given headers.
     *
     * @param  array  $headers
     * @return array
     */
    protected function headers($headers)
    {
        $headerNames = collect($headers)->keys()->map(function ($headerName) {
            return strtolower($headerName);
        })->toArray();

        $headerValues = collect($headers)
            ->map(fn ($header) => implode(', ', $header))
            ->all();

        $headers = array_combine($headerNames, $headerValues);

        return $this->hideParameters($headers,
            Telescope::$hiddenRequestHeaders
        );
    }

    /**
     * Format the given payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function payload($payload)
    {
        return $this->hideParameters($payload,
            Telescope::$hiddenRequestParameters
        );
    }

    /**
     * Hide the given parameters.
     *
     * @param  array  $data
     * @param  array  $hidden
     * @return mixed
     */
    protected function hideParameters($data, $hidden)
    {
        foreach ($hidden as $parameter) {
            if (Arr::get($data, $parameter)) {
                Arr::set($data, $parameter, '********');
            }
        }

        return $data;
    }

    /**
     * Extract the input from the given request.
     *
     * @param  \Illuminate\Http\Client\Request  $request
     * @return array
     */
    protected function input(Request $request)
    {
        if (! $request->isMultipart()) {
            return $request->data();
        }

        return collect($request->data())->mapWithKeys(function ($data) {
            if ($data['contents'] instanceof File) {
                $value = [
                    'name' => $data['filename'] ?? $data['contents']->getClientOriginalName(),
                    'size' => ($data['contents']->getSize() / 1000).'KB',
                    'headers' => $data['headers'] ?? [],
                ];
            } elseif (is_resource($data['contents'])) {
                $filesize = @filesize(stream_get_meta_data($data['contents'])['uri']);

                $value = [
                    'name' => $data['filename'] ?? null,
                    'size' => $filesize ? ($filesize / 1000).'KB' : null,
                    'headers' => $data['headers'] ?? [],
                ];
            } elseif (json_encode($data['contents']) === false) {
                $value = [
                    'name' => $data['filename'] ?? null,
                    'size' => (strlen($data['contents']) / 1000).'KB',
                    'headers' => $data['headers'] ?? [],
                ];
            } else {
                $value = $data['contents'];
            }

            return [$data['name'] => $value];
        })->toArray();
    }

    /**
     * Get the request duration in milliseconds.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return int|null
     */
    protected function duration(Response $response)
    {
        if (property_exists($response, 'transferStats') &&
            $response->transferStats &&
            $response->transferStats->getTransferTime()) {
            return floor($response->transferStats->getTransferTime() * 1000);
        }
    }
}
