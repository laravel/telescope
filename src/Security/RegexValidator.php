<?php

namespace Laravel\Telescope\Security;

use Illuminate\Support\Facades\Log;

class RegexValidator
{
    /**
     * Maximum allowed regex pattern length.
     *
     * @var int
     */
    protected const MAX_PATTERN_LENGTH = 500;

    /**
     * Maximum allowed nesting depth for regex groups.
     *
     * @var int
     */
    protected const MAX_NESTING_DEPTH = 10;

    /**
     * Dangerous regex patterns that could cause ReDoS.
     *
     * @var array
     */
    protected const DANGEROUS_PATTERNS = [
        '/(\*\+|\+\*)/',
        '/(\{\d+,\}\+|\+\{\d+,\})/',
        '/(\*\*|\+\+)/',
        '/(\(\?.*\)\+)/',
        '/(\([^\)]*\)\*\+)/',
    ];

    /**
     * Validate a regex pattern for security and complexity.
     *
     * @param  string  $pattern
     * @param  string  $context
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validate(string $pattern, string $context = 'pattern'): array
    {
        if (strlen($pattern) > self::MAX_PATTERN_LENGTH) {
            $error = 'Regex pattern too long (maximum: '.self::MAX_PATTERN_LENGTH.' characters)';
            self::logInvalidPattern($pattern, $error, $context);

            return ['valid' => false, 'error' => $error];
        }

        if (empty(trim($pattern))) {
            $error = 'Regex pattern cannot be empty';
            self::logInvalidPattern($pattern, $error, $context);

            return ['valid' => false, 'error' => $error];
        }

        $nestingDepth = self::calculateNestingDepth($pattern);
        if ($nestingDepth > self::MAX_NESTING_DEPTH) {
            $error = 'Regex pattern nesting too deep (maximum: '.self::MAX_NESTING_DEPTH." levels, found: {$nestingDepth})";
            self::logInvalidPattern($pattern, $error, $context);

            return ['valid' => false, 'error' => $error];
        }

        foreach (self::DANGEROUS_PATTERNS as $dangerousPattern) {
            if (preg_match($dangerousPattern, $pattern)) {
                $error = 'Regex pattern contains potentially dangerous quantifier combinations that could cause ReDoS';
                self::logInvalidPattern($pattern, $error, $context);

                return ['valid' => false, 'error' => $error];
            }
        }

        $testPattern = $pattern;

        if (! preg_match('/^[\/#~]/', $testPattern)) {
            $testPattern = '#'.$testPattern.'#';
        }

        set_error_handler(function () {
        });
        $result = @preg_match($testPattern, '');
        restore_error_handler();

        if ($result === false) {
            $error = 'Invalid regex pattern syntax';
            self::logInvalidPattern($pattern, $error, $context);

            return ['valid' => false, 'error' => $error];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Calculate nesting depth of parentheses in regex pattern.
     *
     * @param  string  $pattern
     * @return int
     */
    protected static function calculateNestingDepth(string $pattern): int
    {
        $maxDepth = 0;
        $currentDepth = 0;
        $escaped = false;

        for ($i = 0; $i < strlen($pattern); $i++) {
            $char = $pattern[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === '(') {
                $currentDepth++;
                if ($currentDepth > $maxDepth) {
                    $maxDepth = $currentDepth;
                }
            } elseif ($char === ')') {
                $currentDepth--;
            }
        }

        return $maxDepth;
    }

    /**
     * Log invalid regex pattern detection.
     *
     * @param  string  $pattern
     * @param  string  $error
     * @param  string  $context
     * @return void
     */
    protected static function logInvalidPattern(string $pattern, string $error, string $context): void
    {
        Log::warning('Telescope Security: Invalid regex pattern detected', [
            'context' => $context,
            'pattern' => substr($pattern, 0, 100),
            'pattern_length' => strlen($pattern),
            'error' => $error,
            'ip_address' => request()->ip() ?? 'N/A',
            'user_agent' => request()->userAgent() ?? 'N/A',
        ]);
    }
}
