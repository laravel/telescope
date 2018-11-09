<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\View\View;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RequestWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(RequestHandled::class, [$this, 'recordRequest']);
    }

    /**
     * Record an incoming HTTP request.
     *
     * @param  \Illuminate\Foundation\Http\Events\RequestHandled  $event
     * @return void
     */
    public function recordRequest(RequestHandled $event)
    {
        Telescope::recordRequest(IncomingEntry::make([
            'uri' => str_replace($event->request->root(), '', $event->request->fullUrl()) ?: '/',
            'method' => $event->request->method(),
            'controller_action' => optional($event->request->route())->getActionName(),
            'middleware' => optional($event->request->route())->gatherMiddleware() ?? [],
            'headers' => $this->headers($event->request->headers->all()),
            'payload' => $this->payload($event->request->all()),
            'session' => $this->payload($this->sessionVariables($event->request)),
            'response_status' => $event->response->getStatusCode(),
            'response' => $this->response($event->response),
            'duration' => defined('LARAVEL_START') ? floor((microtime(true) - LARAVEL_START) * 1000) : null,
        ]));
    }

    /**
     * Format the given headers.
     *
     * @param  array  $headers
     * @return array
     */
    protected function headers($headers)
    {
        $headers = collect($headers)->map(function ($header) {
            return $header[0];
        })->toArray();

        foreach (Telescope::$hiddenRequestHeaders as $header) {
            if (Arr::get($headers, $header)) {
                Arr::set($headers, $header, '********');
            }
        }

        return $headers;
    }

    /**
     * Format the given payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function payload($payload)
    {
        foreach (Telescope::$hiddenRequestParameters as $parameter) {
            if (Arr::get($payload, $parameter)) {
                Arr::set($payload, $parameter, '********');
            }
        }

        return $payload;
    }

    /**
     * Extract the session variables from the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function sessionVariables(Request $request)
    {
        return $request->hasSession() ? $request->session()->all() : [];
    }

    /**
     * Format the given response object.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return array|string
     */
    protected function response(Response $response)
    {
        $content = $response->getContent();

        if (is_string($content) &&
            is_array(json_decode($content, true)) &&
            json_last_error() === JSON_ERROR_NONE) {
            return $this->contentWithinLimits($content)
                    ? json_decode($response->getContent(), true) : 'Purged By Telescope';
        }

        if ($response instanceof RedirectResponse) {
            return 'Redirected to '.$response->getTargetUrl();
        }

        if ($response instanceof IlluminateResponse && $response->getOriginalContent() instanceof View) {
            return [
                'view' => $response->getOriginalContent()->getPath(),
                'data' => $this->extractDataFromView($response->getOriginalContent())
            ];
        }

        return 'HTML Response';
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
     * Extract the data from the given view in array form.
     *
     * @param  \Illuminate\View\View  $view
     * @return array
     */
    protected function extractDataFromView($view)
    {
        return collect($view->getData())->map(function ($value) {
            if ($value instanceof Model) {
                return get_class($value).':'.$value->getKey();
            } elseif (is_object($value)) {
                return [
                    'class' => get_class($value),
                    'properties' => json_decode(json_encode($value), true),
                ];
            } else {
                return json_decode(json_encode($value), true);
            }
        })->toArray();
    }
}
