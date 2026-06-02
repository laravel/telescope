<?php

namespace Laravel\Telescope\Tests;

use Laravel\Telescope\EndpointMatcher;
use PHPUnit\Framework\TestCase;

class EndpointMatcherTest extends TestCase
{
    public function test_it_matches_exact_paths()
    {
        $this->assertTrue(EndpointMatcher::matches('/api/users', 'GET', 'api/users'));
        $this->assertFalse(EndpointMatcher::matches('/api/posts', 'GET', 'api/users'));
    }

    public function test_it_matches_wildcard_paths()
    {
        $this->assertTrue(EndpointMatcher::matches('/api/users/1', 'GET', 'api/users/*'));
        $this->assertFalse(EndpointMatcher::matches('/api/posts/1', 'GET', 'api/users/*'));
    }

    public function test_it_matches_method_prefixed_patterns()
    {
        $this->assertTrue(EndpointMatcher::matches('/api/users', 'POST', 'POST:api/users'));
        $this->assertFalse(EndpointMatcher::matches('/api/users', 'GET', 'POST:api/users'));
        $this->assertTrue(EndpointMatcher::matches('/api/users', 'GET', '*:api/users'));
    }
}
