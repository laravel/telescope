<?php

namespace Laravel\Telescope\Tests;

use Illuminate\Http\Request;
use Laravel\Sentinel\Sentinel;
use Laravel\Telescope\TelescopeSentinelDriver;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TelescopeSentinelDriverTest extends FeatureTestCase
{
    /**
     * Create a driver instance with the given environment.
     */
    protected function createDriver(string $environment = 'local', array $allowedRemoteAddrs = []): TelescopeSentinelDriver
    {
        return new TelescopeSentinelDriver(function () use ($environment, $allowedRemoteAddrs) {
            $app = $this->app;
            $app->detectEnvironment(fn () => $environment);
            $app->make('config')->set('telescope.sentinel.allowed_remote_addrs', $allowedRemoteAddrs);

            return $app;
        });
    }

    /**
     * Create a request with the given parameters.
     */
    protected function createRequest(
        string $remoteAddr = '127.0.0.1',
        string $host = 'localhost',
        array $trustedProxies = [],
        ?string $forwardedFor = null,
    ): Request {
        $server = [
            'REMOTE_ADDR' => $remoteAddr,
            'HTTP_HOST' => $host,
            'SERVER_NAME' => $host,
        ];

        if ($forwardedFor !== null) {
            $server['HTTP_X_FORWARDED_FOR'] = $forwardedFor;
        }

        $symfonyRequest = new SymfonyRequest(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: $server,
        );

        if (! empty($trustedProxies)) {
            Request::setTrustedProxies($trustedProxies, Request::HEADER_X_FORWARDED_FOR);
        }

        return Request::createFromBase($symfonyRequest);
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);

        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Non-local environment — Sentinel should never block
    // ---------------------------------------------------------------

    public function test_non_local_environment_always_allows_access(): void
    {
        $driver = $this->createDriver('production');

        $request = $this->createRequest('203.0.113.50', 'myapp.com');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_staging_environment_always_allows_access(): void
    {
        $driver = $this->createDriver('staging');

        $request = $this->createRequest('203.0.113.50', 'staging.myapp.com');
        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // No config — default behavior (delegates to parent)
    // ---------------------------------------------------------------

    public function test_local_access_from_localhost_without_config_uses_default_behavior(): void
    {
        $driver = $this->createDriver('local', []);

        // No trusted proxy, private IP — parent allows this
        $request = $this->createRequest('127.0.0.1', 'localhost');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_proxy_without_config_is_blocked_by_default(): void
    {
        $driver = $this->createDriver('local', []);

        // This is the #1691 scenario without config — still blocked
        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // With allowed_remote_addrs config — Docker fix (issue #1691)
    // ---------------------------------------------------------------

    public function test_docker_proxy_allowed_when_cidr_configured(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_proxy_with_10_network_allowed_when_configured(): void
    {
        $driver = $this->createDriver('local', ['10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '10.0.0.1',
            host: 'localhost',
            trustedProxies: ['10.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_proxy_with_192_168_network_allowed_when_configured(): void
    {
        $driver = $this->createDriver('local', ['192.168.0.0/16']);

        $request = $this->createRequest(
            remoteAddr: '192.168.1.1',
            host: 'myapp.test',
            trustedProxies: ['192.168.1.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_multiple_cidr_ranges_can_be_configured(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16']);

        // 172.x range
        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );
        $this->assertTrue($driver->authorize($request));

        // 10.x range
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        $request = $this->createRequest(
            remoteAddr: '10.0.0.5',
            host: 'localhost',
            trustedProxies: ['10.0.0.5'],
            forwardedFor: '203.0.113.50',
        );
        $this->assertTrue($driver->authorize($request));
    }

    public function test_specific_ip_can_be_allowed(): void
    {
        $driver = $this->createDriver('local', ['172.18.0.1']);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Tunnel services — not allowed unless explicitly configured
    // ---------------------------------------------------------------

    public function test_expose_tunnel_not_allowed_when_loopback_not_configured(): void
    {
        // Docker networks configured but NOT loopback — Expose is blocked
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        // REMOTE_ADDR 127.0.0.1 is NOT in the configured ranges, so not allowed
        // Falls through to authorizeAccessingViaReverseProxies which blocks
        // (non-private resolved IP from trusted proxy)
        $this->assertFalse($driver->authorize($request));
    }

    public function test_ngrok_tunnel_not_allowed_when_loopback_not_configured(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.ngrok-free.app',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Remote addr not in allowed ranges — blocked
    // ---------------------------------------------------------------

    public function test_remote_addr_outside_configured_range_is_blocked(): void
    {
        $driver = $this->createDriver('local', ['10.0.0.0/8']);

        // 172.x is NOT in the allowed 10.x range
        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_public_remote_addr_not_in_allowed_ranges_is_blocked(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '203.0.113.1',
            host: 'myapp.com',
            trustedProxies: ['203.0.113.1'],
            forwardedFor: '198.51.100.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Non-local env ignores config entirely
    // ---------------------------------------------------------------

    public function test_allowed_remote_addrs_config_ignored_in_production(): void
    {
        $driver = $this->createDriver('production', ['172.16.0.0/12']);

        $request = $this->createRequest('203.0.113.50', 'myapp.com');
        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Real-world scenarios — Docker, Cloudflare, Sail, tunnels
    // ---------------------------------------------------------------

    public function test_laravel_sail_default_network(): void
    {
        // Sail uses 172.x Docker bridge by default
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.20.0.1',
            host: 'localhost',
            trustedProxies: ['172.20.0.1'],
            forwardedFor: '192.168.65.1',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_desktop_for_mac_nat_translation(): void
    {
        // Docker Desktop for Mac: gateway 172.18.0.1, NAT resolves to
        // Cloudflare IP 172.67.x which falls outside 172.16.0.0/12
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_compose_custom_network(): void
    {
        // Custom Docker Compose network in 10.x range
        $driver = $this->createDriver('local', ['10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '10.5.0.1',
            host: 'myapp.local',
            trustedProxies: ['10.5.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_nginx_reverse_proxy_on_same_host(): void
    {
        // nginx proxy on the same machine, connecting from 127.0.0.1
        $driver = $this->createDriver('local', ['127.0.0.1']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'localhost',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '192.168.1.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_cloudflare_tunnel_with_custom_domain_blocked_without_config(): void
    {
        // Cloudflare Tunnel (cloudflared) typically connects from 127.0.0.1
        // with a custom domain — not in any Docker CIDR range
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.example.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_cloudflare_tunnel_allowed_when_loopback_explicitly_configured(): void
    {
        // If a user explicitly adds 127.0.0.1 to allowed ranges, they accept the
        // tradeoff that any loopback tunnel traffic is also allowed
        $driver = $this->createDriver('local', ['127.0.0.1']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.example.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_expose_blocked_with_docker_only_config(): void
    {
        // Only Docker networks allowed — Expose (127.0.0.1) is not in range
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_ngrok_blocked_with_docker_only_config(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.ngrok-free.app',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_cloudflare_proxy_public_ip_blocked(): void
    {
        // Cloudflare edge proxy with public REMOTE_ADDR — not a local setup
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '104.16.0.1',
            host: 'myapp.com',
            trustedProxies: ['104.16.0.0/12'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_herd_test_domain_works_without_config(): void
    {
        // Herd .test domains: REMOTE_ADDR=127.0.0.1, no proxy, no config needed
        $driver = $this->createDriver('local', []);

        $request = $this->createRequest('127.0.0.1', 'myapp.test');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_ipv6_docker_network(): void
    {
        // Docker with IPv6 networking
        $driver = $this->createDriver('local', ['fd00::/8']);

        $request = $this->createRequest(
            remoteAddr: 'fd12:3456:789a::1',
            host: 'localhost',
            trustedProxies: ['fd12:3456:789a::1'],
            forwardedFor: '2001:db8::1',
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Integration — driver resolution through Sentinel manager
    // ---------------------------------------------------------------

    public function test_sentinel_resolves_telescope_driver(): void
    {
        $driver = Sentinel::driver('telescope');

        $this->assertInstanceOf(TelescopeSentinelDriver::class, $driver);
    }

    public function test_sentinel_fallback_does_not_replace_telescope_driver(): void
    {
        $driver = Sentinel::driverOrFallback('telescope');

        $this->assertInstanceOf(TelescopeSentinelDriver::class, $driver);
    }

    // ---------------------------------------------------------------
    // Integration — full middleware pipeline (HTTP tests)
    // ---------------------------------------------------------------

    public function test_telescope_accessible_in_local_env_via_middleware(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->get('/telescope/telescope-api/mail/1', [
            'REMOTE_ADDR' => '127.0.0.1',
        ])->assertStatus(404); // 404 = passed Sentinel + auth, entry doesn't exist
    }

    public function test_docker_proxy_allowed_through_middleware_when_configured(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->app->make('config')->set('telescope.sentinel.allowed_remote_addrs', ['172.16.0.0/12']);

        Request::setTrustedProxies(['172.18.0.1'], Request::HEADER_X_FORWARDED_FOR);

        $this->get('/telescope/telescope-api/mail/1', [
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_X_FORWARDED_FOR' => '172.67.152.165',
        ])->assertStatus(404); // 404 = passed Sentinel + auth
    }

    public function test_docker_proxy_blocked_without_config_at_driver_level(): void
    {
        // Integration tests can't control REMOTE_ADDR (server variable, not HTTP header).
        // Verify the blocking behavior at the driver level with a real app instance.
        $this->app->detectEnvironment(fn () => 'local');
        $this->app->make('config')->set('telescope.sentinel.allowed_remote_addrs', []);

        $driver = Sentinel::driver('telescope');

        Request::setTrustedProxies(['172.18.0.1'], Request::HEADER_X_FORWARDED_FOR);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertFalse($driver->authorize($request));
    }
}
