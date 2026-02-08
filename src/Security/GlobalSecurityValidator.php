<?php

namespace Laravel\Telescope\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GlobalSecurityValidator
{
    /**
     * Validate the request against global security rules.
     *
     * @param  array  $globalRules
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $path
     * @return array
     */
    public static function validate(array $globalRules, Request $request, string $path): array
    {
        $violations = [];

        foreach ($globalRules as $rule) {
            if (! ($rule['enabled'] ?? true)) {
                continue;
            }

            // Check if path is excluded
            if (static::isPathExcluded($path, $rule['exclude_paths'] ?? [])) {
                continue;
            }

            // Check query parameters
            $queryViolations = static::checkPatternsInData(
                $request->query->all(),
                $rule['patterns'] ?? [],
                $rule['name'],
                $rule['category'],
                'query'
            );
            $violations = array_merge($violations, $queryViolations);

            // Check payload
            $payload = $request->input();
            if (is_array($payload)) {
                $payloadViolations = static::checkPatternsInArray(
                    $payload,
                    $rule['patterns'] ?? [],
                    $rule['name'],
                    $rule['category'],
                    'payload'
                );
                $violations = array_merge($violations, $payloadViolations);
            } elseif (is_string($payload)) {
                $payloadViolations = static::checkPatternsInString(
                    $payload,
                    $rule['patterns'] ?? [],
                    $rule['name'],
                    $rule['category'],
                    'payload'
                );
                $violations = array_merge($violations, $payloadViolations);
            }

            $headers = collect($request->headers->all())->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => is_array($value) ? implode(', ', $value) : $value];
            })->all();

            $headerViolations = static::checkPatternsInData(
                $headers,
                $rule['patterns'] ?? [],
                $rule['name'],
                $rule['category'],
                'header'
            );
            $violations = array_merge($violations, $headerViolations);
        }

        return $violations;
    }

    /**
     * Check if path is excluded from the rule.
     *
     * @param  string  $path
     * @param  array  $excludePaths
     * @return bool
     */
    protected static function isPathExcluded(string $path, array $excludePaths): bool
    {
        if (empty($excludePaths)) {
            return false;
        }

        foreach ($excludePaths as $excludePattern) {
            if ($excludePattern === $path) {
                return true;
            }

            if (Str::contains($excludePattern, '*')) {
                $pattern = '#^'.str_replace(['*', '#'], ['.*', '\#'], $excludePattern).'$#';
                if (preg_match($pattern, $path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check patterns in flat data array.
     *
     * @param  array  $data
     * @param  array  $patterns
     * @param  string  $ruleName
     * @param  string  $category
     * @param  string  $context
     * @return array
     */
    protected static function checkPatternsInData(array $data, array $patterns, string $ruleName, string $category, string $context): array
    {
        $violations = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $foundPatterns = static::findPatternsInString($value, $patterns);
                if (! empty($foundPatterns)) {
                    foreach ($foundPatterns as $pattern) {
                        $violations[] = "{$context} parameter '{$key}' contains {$category} pattern: {$pattern}";
                    }
                }
            } elseif (is_array($value)) {
                $nestedViolations = static::checkPatternsInArray($value, $patterns, $ruleName, $category, "{$context}.{$key}");
                $violations = array_merge($violations, $nestedViolations);
            }
        }

        return $violations;
    }

    /**
     * Check patterns in nested array.
     *
     * @param  array  $data
     * @param  array  $patterns
     * @param  string  $ruleName
     * @param  string  $category
     * @param  string  $prefix
     * @return array
     */
    protected static function checkPatternsInArray(array $data, array $patterns, string $ruleName, string $category, string $prefix = ''): array
    {
        $violations = [];

        foreach ($data as $key => $value) {
            $fieldPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_string($value)) {
                $foundPatterns = static::findPatternsInString($value, $patterns);
                if (! empty($foundPatterns)) {
                    foreach ($foundPatterns as $pattern) {
                        $violations[] = "Field '{$fieldPath}' contains {$category} pattern: {$pattern}";
                    }
                }
            } elseif (is_array($value)) {
                $nestedViolations = static::checkPatternsInArray($value, $patterns, $ruleName, $category, $fieldPath);
                $violations = array_merge($violations, $nestedViolations);
            }
        }

        return $violations;
    }

    /**
     * Check patterns in string.
     *
     * @param  string  $value
     * @param  array  $patterns
     * @param  string  $ruleName
     * @param  string  $category
     * @param  string  $context
     * @return array
     */
    protected static function checkPatternsInString(string $value, array $patterns, string $ruleName, string $category, string $context): array
    {
        $violations = [];
        $foundPatterns = static::findPatternsInString($value, $patterns);

        if (! empty($foundPatterns)) {
            foreach ($foundPatterns as $pattern) {
                $violations[] = "{$context} contains {$category} pattern: {$pattern}";
            }
        }

        return $violations;
    }

    /**
     * Find which patterns match in the string.
     *
     * @param  string  $value
     * @param  array  $patterns
     * @return array
     */
    protected static function findPatternsInString(string $value, array $patterns): array
    {
        $found = [];

        foreach ($patterns as $pattern) {
            if (Str::startsWith($pattern, '/') && Str::endsWith($pattern, '/')) {
                $regex = substr($pattern, 1, -1);

                $validation = RegexValidator::validate($regex, 'global_security_pattern');
                if (! $validation['valid']) {
                    Log::warning('Telescope Security: Invalid or unsafe regex pattern in global security check', [
                        'pattern' => substr($pattern, 0, 100),
                        'error' => $validation['error'],
                    ]);
                    continue;
                }

                try {
                    if (preg_match('#'.str_replace('#', '\#', $regex).'#i', $value)) {
                        $found[] = $pattern;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Telescope Security: Regex execution failed after validation', [
                        'pattern' => substr($pattern, 0, 100),
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            } else {
                if (Str::contains(strtolower($value), strtolower($pattern))) {
                    $found[] = $pattern;
                }
            }
        }

        return $found;
    }

    /**
     * Get default vulnerability patterns.
     *
     * @return array
     */
    public static function getDefaultPatterns(): array
    {
        return [
            [
                'name' => 'Path Traversal',
                'category' => 'path_traversal',
                'description' => 'Detects directory traversal attempts',
                'patterns' => ['../', '..\\', '....//', '....\\\\'],
                'exclude_paths' => [],
                'enabled' => true,
            ],
            [
                'name' => 'SQL Injection',
                'category' => 'sql_injection',
                'description' => 'Detects common SQL injection patterns',
                'patterns' => [
                    "'; DROP",
                    "'; DELETE",
                    "'; UPDATE",
                    "'; INSERT",
                    "' OR '1'='1",
                    "' OR 1=1",
                    "UNION SELECT",
                    "'; --",
                    "'; /*",
                    "'; #",
                ],
                'exclude_paths' => [],
                'enabled' => true,
            ],
            [
                'name' => 'XSS (Cross-Site Scripting)',
                'category' => 'xss',
                'description' => 'Detects XSS attack patterns',
                'patterns' => [
                    '<script',
                    '</script>',
                    'javascript:',
                    'onerror=',
                    'onload=',
                    'onclick=',
                    '<iframe',
                    '<img src=x',
                    'alert(',
                    'eval(',
                ],
                'exclude_paths' => [],
                'enabled' => true,
            ],
            [
                'name' => 'Command Injection',
                'category' => 'command_injection',
                'description' => 'Detects command injection attempts',
                'patterns' => [
                    '; ls',
                    '; cat',
                    '; rm',
                    '; wget',
                    '; curl',
                    '| ls',
                    '| cat',
                    '`whoami`',
                    '$(whoami)',
                    '; id',
                    '| id',
                ],
                'exclude_paths' => [],
                'enabled' => true,
            ],
            [
                'name' => 'LDAP Injection',
                'category' => 'ldap_injection',
                'description' => 'Detects LDAP injection patterns',
                'patterns' => [
                    '*)(&',
                    '*)(|',
                    '*))%00',
                    ')(cn=*',
                ],
                'exclude_paths' => [],
                'enabled' => true,
            ],
            [
                'name' => 'XML Injection',
                'category' => 'xml_injection',
                'description' => 'Detects XML/XXE injection attempts',
                'patterns' => [
                    '<!ENTITY',
                    'SYSTEM "file://',
                    'SYSTEM "http://',
                    '<?xml',
                    '<!DOCTYPE',
                ],
                'exclude_paths' => [],
                'enabled' => true,
            ],
        ];
    }
}

