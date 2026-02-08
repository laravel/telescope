<?php

namespace Laravel\Telescope\Security;

use Illuminate\Support\Facades\Log;

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

        if (preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames) === false) {
            return [];
        }

        if (empty($paramNames[1])) {
            return [];
        }

        foreach ($paramNames[1] as $paramName) {
            if (! preg_match('/^[a-zA-Z0-9_]+$/', $paramName)) {
                return [];
            }
        }

        $regexPattern = preg_replace_callback('/\{[^}]+\}|./', function ($matches) {
            if (preg_match('/^\{[^}]+\}$/', $matches[0])) {
                return '([^/]+)';
            }
            if ($matches[0] === '*') {
                return '.*';
            }

            return preg_quote($matches[0], '#');
        }, $pattern);

        $regexPattern = '#^'.$regexPattern.'$#';

        $validation = \Laravel\Telescope\Security\RegexValidator::validate($regexPattern, 'path_parameter_pattern');
        if (! $validation['valid']) {
            Log::warning('Telescope Security: Invalid regex pattern in path parameter extraction', [
                'pattern' => substr($pattern, 0, 100),
                'error' => $validation['error'],
            ]);

            return [];
        }

        try {
            if (preg_match($regexPattern, $actualPath, $matches)) {
                array_shift($matches);

                foreach ($paramNames[1] as $index => $paramName) {
                    if (isset($matches[$index])) {
                        $params[$paramName] = rawurldecode($matches[$index]);
                    }
                }
            }
        } catch (\Throwable $e) {
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

