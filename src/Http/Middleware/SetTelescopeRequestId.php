<?php

namespace Laravel\Telescope\Http\Middleware;

use Illuminate\Support\Str;

class SetTelescopeRequestId
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        $response = $next($request);
        $response->headers->set('X-Telescope-Request-Id', Str::uuid());

        return $response;
    }
}
