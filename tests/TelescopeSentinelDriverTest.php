<?php

namespace Laravel\Telescope\Tests;

use Illuminate\Http\Request;
use Laravel\Sentinel\Sentinel;
use Laravel\Telescope\TelescopeSentinelDriver;
use RuntimeException;
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

    public function test_expose_tunnel_blocked_with_trusted_proxy(): void
    {
        // Docker networks configured but NOT loopback — Expose is blocked
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        // REMOTE_ADDR 127.0.0.1 is NOT in the configured ranges, so not allowed.
        // Falls through to parent Laravel driver which blocks via
        // authorizeAccessingViaReverseProxies (non-private resolved IP from trusted proxy).
        $this->assertFalse($driver->authorize($request));
    }

    public function test_expose_tunnel_throws_without_trusted_proxy(): void
    {
        // Without trusted proxies, the parent Laravel driver throws RuntimeException
        // for known tunnel hostnames — this protection is preserved.
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
        );

        $this->expectException(RuntimeException::class);
        $driver->authorize($request);
    }

    public function test_ngrok_tunnel_blocked_with_trusted_proxy(): void
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

    public function test_ngrok_tunnel_throws_without_trusted_proxy(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.ngrok-free.app',
        );

        $this->expectException(RuntimeException::class);
        $driver->authorize($request);
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

    public function test_expose_blocked_with_docker_only_config_and_trusted_proxy(): void
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

    public function test_expose_throws_with_docker_only_config_without_trusted_proxy(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
        );

        $this->expectException(RuntimeException::class);
        $driver->authorize($request);
    }

    public function test_ngrok_blocked_with_docker_only_config_and_trusted_proxy(): void
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
    // Additional environments
    // ---------------------------------------------------------------

    public function test_testing_environment_allows_access(): void
    {
        $driver = $this->createDriver('testing');

        $request = $this->createRequest('203.0.113.50', 'myapp.com');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_development_environment_allows_access(): void
    {
        $driver = $this->createDriver('development');

        $request = $this->createRequest('203.0.113.50', 'myapp.com');
        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // IPv6 scenarios
    // ---------------------------------------------------------------

    public function test_ipv6_loopback_works_without_config(): void
    {
        $driver = $this->createDriver('local', []);

        $request = $this->createRequest('::1', 'localhost');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_ipv6_loopback_allowed_when_configured(): void
    {
        $driver = $this->createDriver('local', ['::1']);

        $request = $this->createRequest(
            remoteAddr: '::1',
            host: 'localhost',
            trustedProxies: ['::1'],
            forwardedFor: '2001:db8::1',
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Wildcard trusted proxies (TRUSTED_PROXIES=*)
    // ---------------------------------------------------------------

    public function test_wildcard_trusted_proxies_with_docker_config(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        // TRUSTED_PROXIES=* is common in Sail — represented as 0.0.0.0/0
        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['0.0.0.0/0'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_wildcard_trusted_proxies_without_config_blocks(): void
    {
        $driver = $this->createDriver('local', []);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['0.0.0.0/0'],
            forwardedFor: '172.67.152.165',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Additional tunnel services
    // ---------------------------------------------------------------

    public function test_ngrok_io_domain_throws_without_trusted_proxy(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.ngrok.io',
        );

        $this->expectException(RuntimeException::class);
        $driver->authorize($request);
    }

    public function test_ngrok_io_domain_blocked_with_trusted_proxy(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.ngrok.io',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_ngrok_paid_custom_domain_blocked_without_loopback_config(): void
    {
        // ngrok paid plans allow custom domains like tunnel.mycompany.com
        // Hostname won't match Sentinel's blocklist, but REMOTE_ADDR is still 127.0.0.1
        $driver = $this->createDriver('local', ['172.16.0.0/12', '10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'tunnel.mycompany.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_localhost_run_tunnel_blocked(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.lhr.life',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    public function test_bore_pub_tunnel_blocked(): void
    {
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'bore.pub',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Security tradeoff: loopback in config allows tunnel traffic
    // ---------------------------------------------------------------

    public function test_loopback_config_allows_expose_tunnel_traffic(): void
    {
        // SECURITY TRADEOFF: if the user adds 127.0.0.1 to allowed ranges
        // (e.g. for nginx on same host), Expose traffic is also allowed
        // because Expose also uses REMOTE_ADDR=127.0.0.1
        $driver = $this->createDriver('local', ['127.0.0.1']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'myapp.sharedwithexpose.com',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        // This is allowed — user explicitly accepted this tradeoff
        $this->assertTrue($driver->authorize($request));
    }

    public function test_loopback_config_allows_ngrok_tunnel_traffic(): void
    {
        $driver = $this->createDriver('local', ['127.0.0.1']);

        $request = $this->createRequest(
            remoteAddr: '127.0.0.1',
            host: 'abc123.ngrok-free.app',
            trustedProxies: ['127.0.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // X-Forwarded-For edge cases
    // ---------------------------------------------------------------

    public function test_forwarded_for_chain_with_multiple_ips(): void
    {
        // Multiple proxies: client -> proxy1 -> proxy2 -> app
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '203.0.113.50, 10.0.0.1, 172.18.0.5',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_forwarded_for_with_private_ip_only(): void
    {
        // X-Forwarded-For contains only a private IP
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
            forwardedFor: '192.168.1.100',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_trusted_proxies_configured_but_no_forwarded_headers(): void
    {
        // Trusted proxies exist but request has no X-Forwarded-For
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.18.0.1',
            host: 'localhost',
            trustedProxies: ['172.18.0.1'],
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Docker specific gateway IPs
    // ---------------------------------------------------------------

    public function test_docker_default_bridge_gateway(): void
    {
        // Docker's default bridge network uses 172.17.0.1 as gateway
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.17.0.1',
            host: 'localhost',
            trustedProxies: ['172.17.0.1'],
            forwardedFor: '203.0.113.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_traefik_in_docker(): void
    {
        // Traefik running as Docker container on the same bridge network
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '172.19.0.2',
            host: 'myapp.localhost',
            trustedProxies: ['172.19.0.2'],
            forwardedFor: '192.168.1.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_kubernetes_pod_to_service(): void
    {
        // Kubernetes typically uses 10.x for pod/service networking
        $driver = $this->createDriver('local', ['10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '10.244.0.1',
            host: 'telescope.default.svc.cluster.local',
            trustedProxies: ['10.244.0.1'],
            forwardedFor: '10.0.0.50',
        );

        $this->assertTrue($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Cloud provider proxies in local env
    // ---------------------------------------------------------------

    public function test_aws_alb_private_subnet_allowed_when_configured(): void
    {
        // AWS ALB in private VPC subnet (10.x range)
        $driver = $this->createDriver('local', ['10.0.0.0/8']);

        $request = $this->createRequest(
            remoteAddr: '10.0.1.50',
            host: 'localhost',
            trustedProxies: ['10.0.1.50'],
            forwardedFor: '203.0.113.100',
        );

        $this->assertTrue($driver->authorize($request));
    }

    public function test_aws_alb_public_subnet_blocked(): void
    {
        // AWS ALB with public IP — not a Docker/local setup
        $driver = $this->createDriver('local', ['172.16.0.0/12']);

        $request = $this->createRequest(
            remoteAddr: '54.239.28.85',
            host: 'myapp.com',
            trustedProxies: ['54.239.28.85'],
            forwardedFor: '203.0.113.100',
        );

        $this->assertFalse($driver->authorize($request));
    }

    // ---------------------------------------------------------------
    // Edge cases
    // ---------------------------------------------------------------

    public function test_empty_allowed_remote_addrs_config_does_not_match(): void
    {
        // With an empty config array, no REMOTE_ADDR should match
        $driver = $this->createDriver('local', []);

        $request = $this->createRequest('172.18.0.1', 'localhost');

        // Private REMOTE_ADDR but no config — falls through to parent.
        // Parent allows because IP is private and no trusted proxy is set.
        $this->assertTrue($driver->authorize($request));
    }

    public function test_valet_test_domain_works_without_config(): void
    {
        $driver = $this->createDriver('local', []);

        $request = $this->createRequest('127.0.0.1', 'myapp.test');
        $this->assertTrue($driver->authorize($request));
    }

    public function test_localhost_domain_works_without_config(): void
    {
        $driver = $this->createDriver('local', []);

        $request = $this->createRequest('127.0.0.1', 'myapp.localhost');
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
