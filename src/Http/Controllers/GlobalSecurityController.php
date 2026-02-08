<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Security\GlobalSecurityValidator;
use Laravel\Telescope\Security\RegexValidator;

class GlobalSecurityController extends Controller
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
     * Get all global security rules.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $rules = $this->entries->globalSecurityRules();

        if (empty($rules)) {
            $defaults = GlobalSecurityValidator::getDefaultPatterns();
            return response()->json([
                'rules' => $defaults,
                'defaults_available' => true,
            ]);
        }

        return response()->json([
            'rules' => $rules,
            'defaults_available' => false,
        ]);
    }

    /**
     * Initialize default security rules.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function initializeDefaults()
    {
        $this->entries->initializeDefaultGlobalSecurityRules();

        Cache::forget('telescope:global-security-rules');

        return response()->json([
            'success' => true,
            'rules' => $this->entries->globalSecurityRules(),
        ]);
    }

    /**
     * Add a global security rule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'patterns' => 'required|array|min:1',
            'patterns.*' => 'string|max:500',
            'exclude_paths' => 'nullable|array',
            'exclude_paths.*' => 'string|max:500',
            'enabled' => 'boolean',
        ]);

        foreach ($validated['patterns'] as $pattern) {
            if (preg_match('/^\/.*\/$/', $pattern)) {
                $regexCheck = RegexValidator::validate($pattern, 'global_security_pattern');
                if (! $regexCheck['valid']) {
                    \Illuminate\Support\Facades\Log::warning('Telescope Security: Invalid regex pattern in global security rule', [
                        'pattern' => substr($pattern, 0, 100),
                        'error' => $regexCheck['error'],
                    ]);
                    // Return generic error to client
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid regex pattern. Please check the pattern syntax and complexity.',
                    ], 422);
                }
            }
        }

        $id = $this->entries->addGlobalSecurityRule($validated);

        Cache::forget('telescope:global-security-rules');

        return response()->json([
            'success' => true,
            'id' => $id,
            'rules' => $this->entries->globalSecurityRules(),
        ]);
    }

    /**
     * Update a global security rule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'patterns' => 'required|array|min:1',
            'patterns.*' => 'string|max:500',
            'exclude_paths' => 'nullable|array',
            'exclude_paths.*' => 'string|max:500',
            'enabled' => 'boolean',
        ]);

        foreach ($validated['patterns'] as $pattern) {
            if (preg_match('/^\/.*\/$/', $pattern)) {
                $regexCheck = RegexValidator::validate($pattern, 'global_security_pattern');
                if (! $regexCheck['valid']) {
                    \Illuminate\Support\Facades\Log::warning('Telescope Security: Invalid regex pattern in global security rule', [
                        'pattern' => substr($pattern, 0, 100),
                        'error' => $regexCheck['error'],
                    ]);
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid regex pattern. Please check the pattern syntax and complexity.',
                    ], 422);
                }
            }
        }

        $this->entries->updateGlobalSecurityRule((int) $id, $validated);

        Cache::forget('telescope:global-security-rules');

        return response()->json([
            'success' => true,
            'rules' => $this->entries->globalSecurityRules(),
        ]);
    }

    /**
     * Remove a global security rule.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        $this->entries->removeGlobalSecurityRule((int) $id);
        Cache::forget('telescope:global-security-rules');
        return response()->json([
            'success' => true,
            'rules' => $this->entries->globalSecurityRules(),
        ]);
    }
}

