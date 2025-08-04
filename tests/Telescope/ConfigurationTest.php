<?php

namespace Laravel\Telescope\Tests\Telescope;

use Laravel\Telescope\Tests\FeatureTestCase;

class ConfigurationTest extends FeatureTestCase
{
    /**
     * @test
     */
    public function telescope_config_includes_copy_request_as_curl_setting()
    {
        $config = config('telescope');

        $this->assertArrayHasKey('copy_request_as_curl', $config);
    }

    /**
     * @test
     */
    public function copy_request_as_curl_defaults_to_enabled()
    {
        // Load fresh config to get default value
        $configPath = __DIR__.'/../../config/telescope.php';
        $defaultConfig = require $configPath;

        $this->assertTrue($defaultConfig['copy_request_as_curl']);
    }

    /**
     * @test
     */
    public function copy_request_as_curl_can_be_overridden()
    {
        config(['telescope.copy_request_as_curl' => false]);

        $this->assertFalse(config('telescope.copy_request_as_curl'));

        config(['telescope.copy_request_as_curl' => true]);

        $this->assertTrue(config('telescope.copy_request_as_curl'));
    }
}
