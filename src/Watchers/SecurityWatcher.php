<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Security\GlobalSecurityValidator;
use Laravel\Telescope\Security\PatternValidator;
use Laravel\Telescope\Telescope;

class SecurityWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(RequestHandled::class, [$this, 'recordSecurity']);
    }

    /**
     * Record a security check for an incoming HTTP request.
     *
     * @param  \Illuminate\Foundation\Http\Events\RequestHandled  $event
     * @return void
     */
    public function recordSecurity(RequestHandled $event)
    {
        if (! Telescope::isRecording() || ! ($this->options['enabled'] ?? true)) {
            return;
        }

        $request = $event->request;
        $uri = str_replace($request->root(), '', $request->fullUrl()) ?: '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $queryString = parse_url($uri, PHP_URL_QUERY);

        $whitelistPatterns = $this->getWhitelistPatterns();
        $isWhitelisted = false;
        $violations = [];

        foreach ($whitelistPatterns as $pattern) {
            if (! $this->pathMatches($pattern['path_pattern'], $path, $pattern['is_regex'] ?? false)) {
                continue;
            }

            if (! ($pattern['enabled'] ?? true)) {
                continue;
            }

            if (isset($pattern['method']) && $pattern['method'] !== null) {
                if (strtoupper($pattern['method']) !== strtoupper($request->method())) {
                    continue;
                }
            }

            $patternViolations = PatternValidator::validate($pattern, $request, $path);

            if (empty($patternViolations)) {
                $isWhitelisted = true;
                break;
            }

            $violations = $patternViolations;
            $isWhitelisted = false;
            break;
        }

        $globalRules = $this->getGlobalSecurityRules();
        $globalViolations = GlobalSecurityValidator::validate($globalRules, $request, $path);

        if ($isWhitelisted && empty($globalViolations)) {
            return;
        }

        $suspiciousReasons = [];

        if (! empty($violations)) {
            $suspiciousReasons = array_merge($suspiciousReasons, $violations);
        } elseif (! $isWhitelisted) {
            $suspiciousReasons[] = 'Endpoint not in whitelist';
        }

        if (! empty($globalViolations)) {
            $suspiciousReasons = array_merge($suspiciousReasons, $globalViolations);
        }

        if (empty($suspiciousReasons)) {
            $suspiciousReasons = ['Endpoint not in whitelist'];
        }

        Telescope::recordSecurity(IncomingEntry::make([
            'ip_address' => $request->ip(),
            'uri' => $uri,
            'path' => $path,
            'method' => $request->method(),
            'query_string' => $queryString,
            'query_parameters' => $request->query->all(),
            'headers' => $this->headers($request->headers->all()),
            'payload' => $this->payload($this->input($request)),
            'user_agent' => $request->userAgent(),
            'suspicious_reasons' => $suspiciousReasons,
            'response_status' => $event->response->getStatusCode(),
        ]));
    }

    /**
     * Get all whitelist patterns from config and database.
     *
     * @return array
     */
    protected function getWhitelistPatterns(): array
    {
        $configWhitelist = $this->options['whitelist'] ?? [];
        $legacyPatterns = [];

        foreach ($configWhitelist as $pattern) {
            if (is_string($pattern)) {
                $legacyPatterns[] = [
                    'path_pattern' => $pattern,
                    'method' => null,
                    'query_rules' => [],
                    'payload_rules' => [],
                    'header_rules' => [],
                    'is_regex' => false,
                    'enabled' => true,
                ];
            }
        }

        $dbPatterns = Cache::remember('telescope:security-whitelist', 5, function () {
            try {
                return app(EntriesRepository::class)->securityWhitelist();
            } catch (\Throwable $e) {
                return [];
            }
        });

        return array_merge($legacyPatterns, $dbPatterns);
    }

    /**
     * Get all global security rules from database.
     *
     * @return array
     */
    protected function getGlobalSecurityRules(): array
    {
        return Cache::remember('telescope:global-security-rules', 60, function () {
            try {
                $rules = app(EntriesRepository::class)->globalSecurityRules();
                if (empty($rules)) {
                    return GlobalSecurityValidator::getDefaultPatterns();
                }

                return $rules;
            } catch (\Throwable $e) {
                return GlobalSecurityValidator::getDefaultPatterns();
            }
        });
    }

    /**
     * Check if path matches the pattern.
     *
     * @param  string  $pattern
     * @param  string  $path
     * @param  bool  $isRegex
     * @return bool
     */
    protected function pathMatches(string $pattern, string $path, bool $isRegex): bool
    {
        if (Str::contains($pattern, '{') && Str::contains($pattern, '}')) {
            $regexPattern = preg_replace_callback('/\{[^}]+\}|./', function ($matches) {
                if (preg_match('/^\{[^}]+\}$/', $matches[0])) {
                    return '([a-zA-Z0-9_-]+)';
                }
                if ($matches[0] === '*') {
                    return '.*';
                }

                return preg_quote($matches[0], '#');
            }, $pattern);

            $regexPattern = '#^'.$regexPattern.'$#';

            try {
                if (preg_match($regexPattern, $path, $matches)) {
                    array_shift($matches);
                    foreach ($matches as $capturedValue) {
                        $decoded = rawurldecode($capturedValue);
                        if (str_contains($decoded, '..') ||
                            str_contains($decoded, './') ||
                            str_contains($decoded, '.\\') ||
                            str_contains($decoded, '%2e%2e') ||
                            str_contains($decoded, '%2f')) {
                            Log::warning('Telescope Security: Path traversal attempt detected in path parameter', [
                                'pattern' => $pattern,
                                'path' => $path,
                                'captured_value' => $capturedValue,
                            ]);

                            return false;
                        }
                    }

                    return true;
                }

                return false;
            } catch (\Throwable $e) {
                Log::warning('Telescope Security: Regex error in pathMatches', [
                    'pattern' => $pattern,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        if ($isRegex) {
            try {
                return (bool) @preg_match('#^'.str_replace('#', '\#', $pattern).'$#', $path);
            } catch (\Throwable $e) {
                return false;
            }
        }

        if (Str::contains($pattern, '*')) {
            $pathPattern = str_replace('*', '.*', $pattern);
            try {
                return (bool) @preg_match('#^'.str_replace('#', '\#', $pathPattern).'$#', $path);
            } catch (\Throwable $e) {
                return false;
            }
        }

        return $pattern === $path;
    }

    /**
     * Format the given headers.
     *
     * @param  array  $headers
     * @return array
     */
    protected function headers($headers)
    {
        $headers = collect($headers)
            ->map(fn ($header) => implode(', ', $header))
            ->all();

        return $this->hideParameters($headers,
            Telescope::$hiddenRequestHeaders
        );
    }

    /**
     * Format the given payload.
     *
     * @param  array|string  $payload
     * @return array|string
     */
    protected function payload($payload)
    {
        if (is_string($payload)) {
            return $payload;
        }

        return $this->hideParameters($payload,
            Telescope::$hiddenRequestParameters
        );
    }

    /**
     * Hide the given parameters.
     *
     * @param  array  $data
     * @param  array  $hidden
     * @return mixed
     */
    protected function hideParameters($data, $hidden)
    {
        foreach ($hidden as $parameter) {
            if (Arr::get($data, $parameter)) {
                Arr::set($data, $parameter, '********');
            }
        }

        return $data;
    }

    /**
     * Extract the input from the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|string
     */
    private function input(Request $request)
    {
        if (Str::startsWith(strtolower($request->headers->get('Content-Type') ?? ''), 'text/plain')) {
            return (string) $request->getContent();
        }

        return $request->input();
    }
}
