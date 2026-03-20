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
    protected function createDriver(string $environment = 'local'): TelescopeSentinelDriver
    {
        return new TelescopeSentinelDriver(function () use ($environment) {
            $app = $this->app;
            $app->detectEnvironment(fn () => $environment);

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
    // Normal local development — should allow
    // ---------------------------------------------------------------

    public function test_local_access_from_localhost_is_allowed(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest('127.0.0.1', 'localhost');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_local_access_from_ipv6_loopback_is_allowed(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest('::1', 'localhost');
        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Docker / reverse proxy — should allow (the bug fix)
    // ---------------------------------------------------------------

    public function test_docker_proxy_with_private_remote_addr_is_allowed(): void
    {
        $driver = $this->createDriver();

        // Docker gateway connects from 172.18.0.1 (private), but X-Forwarded-For
        // resolves to a non-private IP due to NAT translation.
        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_proxy_with_10_network_is_allowed(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest(
            remoteAddr: '10.0.0.1',
            host: 'localhost',
            trustedProxies: ['10.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_proxy_with_192_168_network_is_allowed(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest(
            remoteAddr: '192.168.1.1',
            host: 'myapp.test',
            trustedProxies: ['192.168.1.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_docker_proxy_with_wildcard_trusted_proxies_is_allowed(): void
    {
        $driver = $this->createDriver();

        // TRUSTED_PROXIES=* is common in Sail/Docker setups
        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['0.0.0.0/0'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Herd .test domains — should allow
    // ---------------------------------------------------------------

    public function test_herd_test_domain_is_allowed(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest('127.0.0.1', 'myapp.test');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_herd_test_domain_behind_proxy_is_allowed(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'myapp.test',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '192.168.1.100',
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Expose tunnel — should block
    // ---------------------------------------------------------------

    public function test_expose_tunnel_is_blocked(): void
    {
        $driver = $this->createDriver();

        // Expose sets REMOTE_ADDR to 127.0.0.1 (private) but the request
        // is actually from the internet via the tunnel hostname.
        $request = $this->createRequest('127.0.0.1', 'myapp.sharedwithexpose.com');
        $this->assertFalse($driver->authorize($request));
    }

    public function test_expose_tunnel_is_blocked_even_with_trusted_proxy(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_expose_tunnel_is_blocked_with_private_forwarded_ip(): void
    {
        $driver = $this->createDriver();

        // Even if the forwarded IP happens to be private, the tunnel hostname
        // should still cause blocking.
        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '192.168.1.100',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // ngrok tunnel — should block
    // ---------------------------------------------------------------

    public function test_ngrok_free_tunnel_is_blocked(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest('127.0.0.1', 'abc123.ngrok-free.app');
        $this->assertFalse($driver->authorize($request));
    }

    public function test_ngrok_io_tunnel_is_blocked(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest('127.0.0.1', 'abc123.ngrok.io');
        $this->assertFalse($driver->authorize($request));
    }

    public function test_ngrok_tunnel_is_blocked_even_with_trusted_proxy(): void
    {
        $driver = $this->createDriver();

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.ngrok-free.app',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Public IP without proxy — should block
    // ---------------------------------------------------------------

    public function test_public_remote_addr_without_proxy_is_allowed_by_driver(): void
    {
        $driver = $this->createDriver();

        // A public REMOTE_ADDR with no trusted proxy and no tunnel hostname.
        // authorizeAccessingViaReverseProxies() allows this because the request
        // is not claiming to be from a trusted proxy.
        $request = $this->createRequest('203.0.113.50', 'myapp.com');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_public_ip_via_trusted_proxy_is_blocked(): void
    {
        $driver = $this->createDriver();

        // A public IP coming through a trusted proxy with a non-private resolved IP
        // should be blocked by the parent's authorizeAccessingViaReverseProxies().
        $request = $this->createRequest(
            remoteAddr: '203.0.113.1',
            host: 'myapp.com',
            trustedProxies: ['203.0.113.1'],
            forwardedFor: '198.51.100.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Tunnel blocking in non-local env — should not block
    // ---------------------------------------------------------------

    public function test_tunnel_hostnames_are_not_blocked_in_production(): void
    {
        $driver = $this->createDriver('production');

        $request = $this->createRequest('127.0.0.1', 'myapp.sharedwithexpose.com');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_ngrok_hostname_is_not_blocked_in_production(): void
    {
        $driver = $this->createDriver('production');

        $request = $this->createRequest('127.0.0.1', 'abc123.ngrok-free.app');
        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Integration — verify driver resolution through Sentinel manager
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
    // Integration — full middleware pipeline (HTTP test)
    // ---------------------------------------------------------------

    public function test_telescope_accessible_in_local_env_via_middleware(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->get('/telescope/telescope-api/mail/1', [
            'REMOTE_ADDR' => '127.0.0.1',
        ])->assertStatus(404); // 404 = passed Sentinel + auth, entry just doesn't exist
    }

    public function test_telescope_blocked_via_tunnel_hostname_through_middleware(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->get(
            'http://myapp.sharedwithexpose.com/telescope/telescope-api/mail/1',
            ['REMOTE_ADDR' => '127.0.0.1'],
        )->assertStatus(401);
    }

    public function test_docker_proxy_allowed_through_full_middleware_stack(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        Request::setTrustedProxies(['172.18.0.1'], Request::HEADER_X_FORWARDED_FOR);

        $this->get('/telescope/telescope-api/mail/1', [
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_X_FORWARDED_FOR' => '172.67.152.165',
        ])->assertStatus(404); // 404 = passed Sentinel + auth, entry just doesn't exist
    }
}
