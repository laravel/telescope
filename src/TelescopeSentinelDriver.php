<?php

namespace Laravel\Telescope;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sentinel\Drivers\Driver;

class TelescopeSentinelDriver extends Driver
{
    /**
     * The tunnel service hostnames that should be blocked.
     *
     * @var array<int, string>
     */
    protected $tunnelHostnames = [
        '.sharedwithexpose.com',
        '.ngrok-free.app',
        '.ngrok.io',
    ];

    /**
     * Authorize access for the request.
     */
    public function authorize(Request $request): bool
    {
        if (! $this->app()->environment('local')) {
            return true;
        }

        if ($this->isTunnelRequest($request)) {
            return false;
        }

        $remoteAddr = $request->server->get('REMOTE_ADDR', '');

        if ($remoteAddr !== '' && $this->isPrivateIp($remoteAddr)) {
            return true;
        }

        return $this->authorizeAccessingViaReverseProxies($request);
    }

    /**
     * Determine if the request is coming through a known tunnel service.
     */
    protected function isTunnelRequest(Request $request): bool
    {
        return Str::endsWith($request->host(), $this->tunnelHostnames);
    }
}
