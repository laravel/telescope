<?php

namespace Laravel\Telescope\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Telescope\Security\RegexValidator;

class PathParameterExtractor
{
    /**
     * Extract path parameters from a path pattern and actual path.
     *
     * @param  string  $pattern
     * @param  string  $actualPath
     * @return array
     */
    public static function extract(string $pattern, string $actualPath): array
    {
        $params = [];

        // Extract parameter names from pattern (e.g., {id}, {slug})
        if (preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames) === false) {
            return [];
        }

        if (empty($paramNames[1])) {
            return [];
        }

        // Validate parameter names (alphanumeric and underscore only)
        foreach ($paramNames[1] as $paramName) {
            if (! preg_match('/^[a-zA-Z0-9_]+$/', $paramName)) {
                // Invalid parameter name, skip extraction
                return [];
            }
        }

        // Convert pattern to regex
        // First escape special regex characters in the static parts
        $regexPattern = preg_replace_callback('/\{[^}]+\}|./', function ($matches) {
            if (preg_match('/^\{[^}]+\}$/', $matches[0])) {
                // This is a parameter placeholder, replace with capture group
                return '([^/]+)';
            }
            if ($matches[0] === '*') {
                // Wildcard matches any characters including slashes
                return '.*';
            }
            // This is a regular character, escape it for regex
            return preg_quote($matches[0], '#');
        }, $pattern);
        
        $regexPattern = '#^'.$regexPattern.'$#';

        // Validate final regex pattern before use to prevent ReDoS
        $validation = RegexValidator::validate($regexPattern, 'path_parameter_pattern');
        if (! $validation['valid']) {
            Log::warning('Telescope Security: Invalid regex pattern in path parameter extraction', [
                'pattern' => substr($pattern, 0, 100),
                'error' => $validation['error'],
            ]);
            return [];
        }

        // Match actual path against pattern with error handling
        try {
            if (preg_match($regexPattern, $actualPath, $matches)) {
                // Skip first match (full match), get parameter values
                array_shift($matches);

                // Map parameter names to values
                foreach ($paramNames[1] as $index => $paramName) {
                    if (isset($matches[$index])) {
                        // Decode URL-encoded values
                        $params[$paramName] = rawurldecode($matches[$index]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Regex error, log and return empty
            Log::warning('Telescope Security: Path parameter extraction failed', [
                'pattern' => substr($pattern, 0, 100),
                'path' => substr($actualPath, 0, 100),
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        return $params;
    }
}

