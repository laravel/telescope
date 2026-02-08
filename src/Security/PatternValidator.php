<?php

namespace Laravel\Telescope\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PatternValidator
{
    /**
     * Validate the request against the whitelist pattern.
     * Note: This method assumes the path already matches the pattern.
     *
     * @param  array  $pattern
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $path
     * @return array
     */
    public static function validate(array $pattern, Request $request, string $path): array
    {
        $violations = [];

        if (! empty($pattern['path_params_rules'])) {
            $pathParams = \Laravel\Telescope\Security\PathParameterExtractor::extract($pattern['path_pattern'], $path);
            $pathParamViolations = static::validatePathParameters($pattern['path_params_rules'], $pathParams);
            $violations = array_merge($violations, $pathParamViolations);
        }

        $queryParams = $request->query->all();
        foreach ($queryParams as $key => $value) {
            $stringValue = (string) $value;
            if (Str::contains($stringValue, '../') || Str::contains($stringValue, '..\\')) {
                $violations[] = "Query parameter '{$key}' contains path traversal attempt";
            }
        }

        $payload = $request->input();
        if (is_array($payload)) {
            $payloadViolations = static::checkPathTraversalInArray($payload, 'payload');
            $violations = array_merge($violations, $payloadViolations);
        } elseif (is_string($payload) && (Str::contains($payload, '../') || Str::contains($payload, '..\\'))) {
            $violations[] = 'Payload contains path traversal attempt';
        }

        if (! empty($pattern['query_rules'])) {
            $queryViolations = static::validateQueryParameters($pattern['query_rules'], $request);
            $violations = array_merge($violations, $queryViolations);

            $allowedParams = array_keys($pattern['query_rules']);
            $actualParams = array_keys($request->query->all());
            $unknownParams = array_diff($actualParams, $allowedParams);

            if (! empty($unknownParams)) {
                foreach ($unknownParams as $unknownParam) {
                    $violations[] = "Query parameter '{$unknownParam}' is not whitelisted";
                }
            }
        }

        if (! empty($pattern['payload_rules'])) {
            $payloadViolations = static::validatePayload($pattern['payload_rules'], $request);
            $violations = array_merge($violations, $payloadViolations);

            $allowedFields = array_keys($pattern['payload_rules']);
            $payload = $request->input();
            if (is_array($payload)) {
                $actualFields = array_keys($payload);
                $unknownFields = array_diff($actualFields, $allowedFields);

                if (! empty($unknownFields)) {
                    foreach ($unknownFields as $unknownField) {
                        $violations[] = "Payload field '{$unknownField}' is not whitelisted";
                    }
                }
            }
        }

        if (! empty($pattern['header_rules'])) {
            $headerViolations = static::validateHeaders($pattern['header_rules'], $request);
            $violations = array_merge($violations, $headerViolations);

            $allowedHeaders = array_map('strtolower', array_keys($pattern['header_rules']));
            $actualHeaders = collect($request->headers->all())->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->keys()->all();
            $unknownHeaders = array_diff($actualHeaders, $allowedHeaders);

            if (! empty($unknownHeaders)) {
                foreach ($unknownHeaders as $unknownHeader) {
                    $violations[] = "Header '{$unknownHeader}' is not whitelisted";
                }
            }
        }

        return $violations;
    }

    /**
     * Validate path parameters against rules.
     *
     * @param  array  $rules
     * @param  array  $pathParams
     * @return array
     */
    protected static function validatePathParameters(array $rules, array $pathParams): array
    {
        $violations = [];

        foreach ($rules as $key => $rule) {
            $value = $pathParams[$key] ?? null;

            if ($value === null && ($rule['required'] ?? false)) {
                $violations[] = "Path parameter '{$key}' is required but missing";
                continue;
            }

            if ($value === null) {
                continue;
            }

            $violations = array_merge($violations, static::validateValue($key, $value, $rule, 'path parameter'));
        }

        return $violations;
    }

    /**
     * Validate query parameters against rules.
     *
     * @param  array  $rules
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected static function validateQueryParameters(array $rules, Request $request): array
    {
        $violations = [];
        $queryParams = $request->query->all();

        foreach ($rules as $key => $rule) {
            $value = $queryParams[$key] ?? null;

            if ($value === null && ($rule['required'] ?? false)) {
                $violations[] = "Query parameter '{$key}' is required";
                continue;
            }

            if ($value === null) {
                continue;
            }

            $violations = array_merge($violations, static::validateValue($key, $value, $rule, 'query'));
        }

        return $violations;
    }

    /**
     * Validate payload against rules.
     *
     * @param  array  $rules
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected static function validatePayload(array $rules, Request $request): array
    {
        $violations = [];
        $payload = $request->input();

        foreach ($rules as $key => $rule) {
            $value = data_get($payload, $key);

            if ($value === null && ($rule['required'] ?? false)) {
                $violations[] = "Payload field '{$key}' is required";
                continue;
            }

            if ($value === null) {
                continue;
            }

            $violations = array_merge($violations, static::validateValue($key, $value, $rule, 'payload'));
        }

        return $violations;
    }

    /**
     * Validate headers against rules.
     *
     * @param  array  $rules
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected static function validateHeaders(array $rules, Request $request): array
    {
        $violations = [];
        $headers = collect($request->headers->all())->mapWithKeys(function ($value, $key) {
            return [strtolower($key) => is_array($value) ? implode(', ', $value) : $value];
        })->all();

        foreach ($rules as $key => $rule) {
            $headerKey = strtolower($key);
            $value = $headers[$headerKey] ?? null;

            if ($value === null && ($rule['required'] ?? false)) {
                $violations[] = "Header '{$key}' is required";
                continue;
            }

            if ($value === null) {
                continue;
            }

            $violations = array_merge($violations, static::validateValue($key, $value, $rule, 'header'));
        }

        return $violations;
    }

    /**
     * Validate a single value against rules.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $rule
     * @param  string  $context
     * @return array
     */
    protected static function validateValue(string $key, $value, array $rule, string $context): array
    {
        $violations = [];
        $stringValue = (string) $value;

        if ($rule['prevent_path_traversal'] ?? true) {
            if (Str::contains($stringValue, '../') || Str::contains($stringValue, '..\\')) {
                $violations[] = "{$context} field '{$key}' contains path traversal attempt";
            }
        }

        if (! empty($rule['forbidden_patterns'])) {
            foreach ($rule['forbidden_patterns'] as $forbiddenPattern) {
                if (Str::contains($stringValue, $forbiddenPattern)) {
                    $violations[] = "{$context} field '{$key}' contains forbidden pattern: {$forbiddenPattern}";
                }
            }
        }

        if (isset($rule['type'])) {
            $allowedTypes = is_array($rule['type']) ? $rule['type'] : [$rule['type']];
            $typeViolations = [];

            $isValid = false;
            foreach ($allowedTypes as $type) {
                $typeViolation = static::validateType($key, $value, $type, $context);
                if ($typeViolation === null) {
                    $isValid = true;
                    break;
                }
                $typeViolations[] = $typeViolation;
            }

            if (! $isValid && ! empty($typeViolations)) {
                $typesList = implode(' or ', $allowedTypes);
                $violations[] = "{$context} field '{$key}' must be one of: {$typesList}";
            }
        }

        if (isset($rule['regex']) && ! empty($rule['regex'])) {
            $regex = $rule['regex'];
            if (! preg_match('/^[\/#~]/', $regex)) {
                $regex = '#^'.$regex.'$#';
            }

            $validation = \Laravel\Telescope\Security\RegexValidator::validate($regex, "validation_rule_{$context}_{$key}");
            if (! $validation['valid']) {
                $violations[] = "{$context} field '{$key}' has an invalid regex pattern: {$validation['error']}";
            } else {
                try {
                    if (! preg_match($regex, $stringValue)) {
                        $violations[] = "{$context} field '{$key}' does not match required pattern";
                    }
                } catch (\Throwable $e) {
                    Log::warning('Telescope Security: Regex execution failed after validation', [
                        'context' => $context,
                        'field' => $key,
                        'pattern' => substr($regex, 0, 100),
                        'error' => $e->getMessage(),
                    ]);
                    $violations[] = "{$context} field '{$key}' regex execution failed";
                }
            }
        } elseif (isset($rule['type']) && $rule['type'] === 'custom' && empty($rule['regex'])) {
            $violations[] = "{$context} field '{$key}' requires a regex pattern for custom type";
        }

        if (isset($rule['min_length']) && strlen($stringValue) < $rule['min_length']) {
            $violations[] = "{$context} field '{$key}' is too short (minimum: {$rule['min_length']})";
        }

        if (isset($rule['max_length']) && strlen($stringValue) > $rule['max_length']) {
            $violations[] = "{$context} field '{$key}' is too long (maximum: {$rule['max_length']})";
        }

        return $violations;
    }

    /**
     * Validate value type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string  $type
     * @param  string  $context
     * @return string|null
     */
    protected static function validateType(string $key, $value, string $type, string $context): ?string
    {
        switch ($type) {
            case 'string':
                if (is_numeric($value)) {
                    return "{$context} field '{$key}' expected string but got numeric value";
                }
                break;

            case 'integer':
                if (! is_numeric($value) || (int) $value != $value || (float) $value != (int) $value) {
                    return "{$context} field '{$key}' expected integer but got ".gettype($value);
                }
                break;

            case 'numeric':
                if (! is_numeric($value)) {
                    return "{$context} field '{$key}' expected numeric but got ".gettype($value);
                }
                break;

            case 'string_or_numeric':
                if (! is_string($value) && ! is_numeric($value)) {
                    return "{$context} field '{$key}' must be string or numeric";
                }
                break;

            case 'email':
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$context} field '{$key}' is not a valid email address";
                }
                break;

            case 'url':
                if (! filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$context} field '{$key}' is not a valid URL";
                }
                break;

            case 'uuid':
                $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
                $stringValue = (string) $value;
                if (! preg_match($uuidPattern, $stringValue)) {
                    return "{$context} field '{$key}' is not a valid UUID";
                }
                break;

            case 'alphanumeric':
                $stringValue = (string) $value;
                if (! preg_match('/^[a-zA-Z0-9]+$/', $stringValue)) {
                    return "{$context} field '{$key}' must be alphanumeric";
                }
                break;

            case 'custom':
                break;
        }

        return null;
    }

    /**
     * Recursively check for path traversal in array values.
     *
     * @param  array  $data
     * @param  string  $prefix
     * @return array
     */
    protected static function checkPathTraversalInArray(array $data, string $prefix = ''): array
    {
        $violations = [];

        foreach ($data as $key => $value) {
            $fieldPath = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_string($value)) {
                if (Str::contains($value, '../') || Str::contains($value, '..\\')) {
                    $violations[] = "Field '{$fieldPath}' contains path traversal attempt";
                }
            } elseif (is_array($value)) {
                $nestedViolations = static::checkPathTraversalInArray($value, $fieldPath);
                $violations = array_merge($violations, $nestedViolations);
            }
        }

        return $violations;
    }
}
