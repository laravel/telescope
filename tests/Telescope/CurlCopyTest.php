<?php

namespace Laravel\Telescope\Tests\Telescope;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;

class CurlCopyTest extends FeatureTestCase
{
    /**
     * @test
     */
    public function copy_request_as_curl_is_enabled_by_default()
    {
        $scriptVariables = Telescope::scriptVariables();

        $this->assertTrue($scriptVariables['copyRequestAsCurl']);
    }

    /**
     * @test
     */
    public function copy_request_as_curl_can_be_disabled_via_config()
    {
        config(['telescope.copy_request_as_curl' => false]);

        $scriptVariables = Telescope::scriptVariables();

        $this->assertFalse($scriptVariables['copyRequestAsCurl']);
    }

    /**
     * @test
     */
    public function copy_request_as_curl_uses_default_when_config_is_missing()
    {
        // Remove the config key entirely
        $config = config('telescope');
        unset($config['copy_request_as_curl']);
        config(['telescope' => $config]);

        // When config is missing, it should use the default value (true) from the scriptVariables method
        $scriptVariables = Telescope::scriptVariables();
        $this->assertTrue($scriptVariables['copyRequestAsCurl']);

        // Test explicit config override
        config(['telescope.copy_request_as_curl' => false]);
        $scriptVariables = Telescope::scriptVariables();
        $this->assertFalse($scriptVariables['copyRequestAsCurl']);
    }

    /**
     * @test
     */
    public function script_variables_includes_curl_copy_setting()
    {
        $scriptVariables = Telescope::scriptVariables();

        $this->assertArrayHasKey('copyRequestAsCurl', $scriptVariables);
        $this->assertIsBool($scriptVariables['copyRequestAsCurl']);
    }

    /**
     * @test
     */
    public function script_variables_maintains_existing_variables()
    {
        $scriptVariables = Telescope::scriptVariables();

        // Ensure we didn't break existing script variables
        $this->assertArrayHasKey('path', $scriptVariables);
        $this->assertArrayHasKey('timezone', $scriptVariables);
        $this->assertArrayHasKey('recording', $scriptVariables);
        $this->assertArrayHasKey('copyRequestAsCurl', $scriptVariables);
    }
}
