<?php

namespace Laravel\Telescope;

use Illuminate\Http\Request;
use Laravel\Sentinel\Drivers\Laravel;
use Symfony\Component\HttpFoundation\IpUtils;

class TelescopeSentinelDriver extends Laravel
{
    /**
     * Authorize access for the request.
     *
     * @throws \RuntimeException
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

        return parent::authorize($request);
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
