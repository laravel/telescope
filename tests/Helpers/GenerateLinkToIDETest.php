<?php

namespace Laravel\Telescope\Tests\Helpers;

use Illuminate\Support\Str;
use Laravel\Telescope\Helpers\GenerateLinkToIDE;
use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\TestCase;

class GenerateLinkToIDETest extends TestCase
{
    /** @test */
    public function it_generates_a_link_to_phpstorm()
    {
        $action = sprintf('%s@%s', TelescopeServiceProvider::class, 'boot');
        $link = GenerateLinkToIDE::make($action);
        $linkParts = parse_url($link);

        $this->assertEquals('phpstorm', $linkParts['scheme'] ?? null, json_encode($linkParts));
        $this->assertEquals('open', $linkParts['host']);
        $this->assertTrue(Str::of($link)->contains('TelescopeServiceProvider'));
    }

    /** @test */
    public function editor_is_configurable()
    {
        $this->app->get('config')->set('telescope.editor', 'atom');

        $action = sprintf('%s@%s', TelescopeServiceProvider::class, 'boot');
        $link = GenerateLinkToIDE::make($action);
        $linkParts = parse_url($link);

        $this->assertEquals('atom', $linkParts['scheme']);
        $this->assertEquals('core', $linkParts['host']);
        $this->assertTrue(Str::of($link)->contains('TelescopeServiceProvider'));
    }
}
