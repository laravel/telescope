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
        '/(\*\+|\+\*)/',              // Star followed by plus or vice versa
        '/(\{\d+,\}\+|\+\{\d+,\})/',  // Quantifier followed by plus
        '/(\*\*|\+\+)/',              // Repeated quantifiers
        '/(\(\?.*\)\+)/',             // Non-capturing group with quantifier
        '/(\([^\)]*\)\*\+)/',         // Group with nested quantifiers
    ];

    /**
     * Validate a regex pattern for security and complexity.
     *
     * @param  string  $pattern
     * @param  string  $context
     * @return array  ['valid' => bool, 'error' => string|null]
     */
    public static function validate(string $pattern, string $context = 'pattern'): array
    {
        // Check pattern length
        if (strlen($pattern) > self::MAX_PATTERN_LENGTH) {
            $error = "Regex pattern too long (maximum: ".self::MAX_PATTERN_LENGTH." characters)";
            self::logInvalidPattern($pattern, $error, $context);
            return ['valid' => false, 'error' => $error];
        }

        // Check for empty pattern
        if (empty(trim($pattern))) {
            $error = "Regex pattern cannot be empty";
            self::logInvalidPattern($pattern, $error, $context);
            return ['valid' => false, 'error' => $error];
        }

        // Check nesting depth
        $nestingDepth = self::calculateNestingDepth($pattern);
        if ($nestingDepth > self::MAX_NESTING_DEPTH) {
            $error = "Regex pattern nesting too deep (maximum: ".self::MAX_NESTING_DEPTH." levels, found: {$nestingDepth})";
            self::logInvalidPattern($pattern, $error, $context);
            return ['valid' => false, 'error' => $error];
        }

        // Check for dangerous ReDoS patterns
        foreach (self::DANGEROUS_PATTERNS as $dangerousPattern) {
            if (preg_match($dangerousPattern, $pattern)) {
                $error = "Regex pattern contains potentially dangerous quantifier combinations that could cause ReDoS";
                self::logInvalidPattern($pattern, $error, $context);
                return ['valid' => false, 'error' => $error];
            }
        }

        // Validate regex syntax by attempting to use it
        $testPattern = $pattern;
        
        // If pattern doesn't start with delimiter, wrap it
        if (! preg_match('/^[\/#~]/', $testPattern)) {
            $testPattern = '#'.$testPattern.'#';
        }

        // Try to compile the regex
        set_error_handler(function () {});
        $result = @preg_match($testPattern, '');
        restore_error_handler();

        if ($result === false) {
            $error = "Invalid regex pattern syntax";
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
            'pattern' => substr($pattern, 0, 100), // Log first 100 chars only
            'pattern_length' => strlen($pattern),
            'error' => $error,
            'ip_address' => request()->ip() ?? 'N/A',
            'user_agent' => request()->userAgent() ?? 'N/A',
        ]);
    }

    /**
     * Check if a regex pattern is safe to use without full validation.
     * Used for quick checks in hot paths.
     *
     * @param  string  $pattern
     * @return bool
     */
    public static function isSafe(string $pattern): bool
    {
        // Quick length check
        if (strlen($pattern) > self::MAX_PATTERN_LENGTH) {
            return false;
        }

        // Quick dangerous pattern check
        foreach (self::DANGEROUS_PATTERNS as $dangerousPattern) {
            if (preg_match($dangerousPattern, $pattern)) {
                return false;
            }
        }

        return true;
    }
}

