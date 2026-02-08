<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Security\RegexValidator;

class SecurityWhitelistController extends Controller
{
    /**
     * The entry repository implementation.
     *
     * @var \Laravel\Telescope\Contracts\EntriesRepository
     */
    protected $entries;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $entries
     * @return void
     */
    public function __construct(EntriesRepository $entries)
    {
        $this->entries = $entries;
    }

    /**
     * Get all of the security whitelist patterns.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            'patterns' => $this->entries->securityWhitelist(),
        ]);
    }

    /**
     * Add a pattern to the security whitelist.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'path_pattern' => 'required|string|max:500',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD',
            'is_regex' => 'boolean',
            'enabled' => 'boolean',
            'path_params_rules' => 'nullable|array',
            'query_rules' => 'nullable|array',
            'payload_rules' => 'nullable|array',
            'header_rules' => 'nullable|array',
        ]);

        if ($validated['is_regex'] ?? false) {
            $regexCheck = RegexValidator::validate($validated['path_pattern'], 'whitelist_path_pattern');
            if (! $regexCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => $regexCheck['error'],
                ], 422);
            }
        }

        $regexError = $this->validateRulesRegex($validated);
        if ($regexError) {
            return response()->json([
                'success' => false,
                'error' => $regexError,
            ], 422);
        }

        $id = $this->entries->addSecurityWhitelist($validated);

        Cache::forget('telescope:security-whitelist');

        return response()->json([
            'success' => true,
            'id' => $id,
            'patterns' => $this->entries->securityWhitelist(),
        ]);
    }

    /**
     * Update a security whitelist pattern.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'path_pattern' => 'required|string|max:500',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD',
            'is_regex' => 'boolean',
            'enabled' => 'boolean',
            'path_params_rules' => 'nullable|array',
            'query_rules' => 'nullable|array',
            'payload_rules' => 'nullable|array',
            'header_rules' => 'nullable|array',
        ]);

        if ($validated['is_regex'] ?? false) {
            $regexCheck = RegexValidator::validate($validated['path_pattern'], 'whitelist_path_pattern');
            if (! $regexCheck['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => $regexCheck['error'],
                ], 422);
            }
        }

        $regexError = $this->validateRulesRegex($validated);
        if ($regexError) {
            return response()->json([
                'success' => false,
                'error' => $regexError,
            ], 422);
        }

        $this->entries->updateSecurityWhitelist((int) $id, $validated);

        Cache::forget('telescope:security-whitelist');

        return response()->json([
            'success' => true,
            'patterns' => $this->entries->securityWhitelist(),
        ]);
    }

    /**
     * Remove a pattern from the security whitelist.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $this->entries->removeSecurityWhitelist((int) $id);

        Cache::forget('telescope:security-whitelist');

        return response()->json([
            'success' => true,
            'patterns' => $this->entries->securityWhitelist(),
        ]);
    }

    /**
     * Validate regex patterns in rules.
     *
     * @param  array  $validated
     * @return string|null
     */
    protected function validateRulesRegex(array $validated): ?string
    {
        $ruleSets = [
            'path_params_rules' => 'Path parameter',
            'query_rules' => 'Query parameter',
            'payload_rules' => 'Payload',
            'header_rules' => 'Header',
        ];

        foreach ($ruleSets as $ruleKey => $ruleType) {
            if (empty($validated[$ruleKey])) {
                continue;
            }

            foreach ($validated[$ruleKey] as $fieldName => $rule) {
                if (isset($rule['regex']) && ! empty($rule['regex'])) {
                    $regexCheck = RegexValidator::validate($rule['regex'], "{$ruleType} rule for '{$fieldName}'");
                    if (! $regexCheck['valid']) {
                        return "{$ruleType} rule for '{$fieldName}': ".$regexCheck['error'];
                    }
                }
            }
        }

        return null;
    }
}
