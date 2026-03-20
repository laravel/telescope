<?php

namespace Laravel\Telescope;

use Illuminate\Http\Request;
use Laravel\Sentinel\Drivers\Driver;
use Symfony\Component\HttpFoundation\IpUtils;

class TelescopeSentinelDriver extends Driver
{
    /**
     * Authorize access for the request.
     */
    public function authorize(Request $request): bool
    {
        if (! $this->app()->environment('local')) {
            return true;
        }

        $remoteAddr = $request->server->get('REMOTE_ADDR', '');

        if ($remoteAddr !== '' && $this->isAllowedRemoteAddr($remoteAddr)) {
            return true;
        }

        return $this->authorizeAccessingViaReverseProxies($request);
    }

    /**
     * Determine if the raw REMOTE_ADDR is in the configured allowed ranges.
     */
    protected function isAllowedRemoteAddr(string $remoteAddr): bool
    {
        $allowed = $this->app()->make('config')->get('telescope.sentinel.allowed_remote_addrs', []);

        if (empty($allowed)) {
            return false;
        }

        return IpUtils::checkIp($remoteAddr, $allowed);
    }
}
