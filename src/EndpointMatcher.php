<?php

namespace Laravel\Telescope;

use Illuminate\Support\Str;

class EndpointMatcher
{
    /**
     * Determine if the given URI and method match the endpoint pattern.
     *
     * Patterns support Laravel-style wildcards (e.g. "api/users/*") and
     * optional HTTP method prefixes (e.g. "GET:api/users" or "*:api/users").
     *
     * @param  string  $uri
     * @param  string  $method
     * @param  string  $pattern
     * @return bool
     */
    public static function matches(string $uri, string $method, string $pattern): bool
    {
        $pattern = trim($pattern);

        if ($pattern === '') {
            return false;
        }

        if (str_contains($pattern, ':')) {
            [$patternMethod, $patternPath] = explode(':', $pattern, 2);

            $patternMethod = strtoupper(trim($patternMethod));
            $pattern = trim($patternPath);

            if ($patternMethod !== '*' && $patternMethod !== strtoupper($method)) {
                return false;
            }
        }

        $uri = '/'.ltrim($uri, '/');
        $pattern = '/'.ltrim($pattern, '/');

        return Str::is($pattern, $uri);
    }

    /**
     * Determine if the given URI and method match any of the endpoint patterns.
     *
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $patterns
     * @return bool
     */
    public static function matchesAny(string $uri, string $method, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (static::matches($uri, $method, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
