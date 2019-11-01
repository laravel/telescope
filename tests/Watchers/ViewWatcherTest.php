<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Support\Facades\View;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;

class ViewWatcherTest extends FeatureTestCase
{
    protected $viewsDirectory = __DIR__.'/../stubs/views';

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        View::addNamespace('tests', $this->viewsDirectory);

        View::composer('tests::welcome', ViewComposer::class);
        View::composer('tests::layouts.master', function ($view) {
            return $view->with('foo', 'bar');
        });
        View::creator('tests::layouts.*', function ($view) {
            return $view->with('test', 'foo');
        });
    }

    public function test_view_watcher_registers_views()
    {
        View::make('tests::welcome')->render();

        $entries = $this->loadTelescopeEntries();

        $this->assertSame(EntryType::VIEW, $entries[0]->type);
        $this->assertSame('tests::welcome', $entries[0]->content['name']);
        $this->assertSame($this->viewsDirectory.'/welcome.blade.php', $entries[0]->content['path']);
        $this->assertSame(['bar'], $entries[0]->content['data']);
        $this->assertSame([['name' => ViewComposer::class.'@compose', 'type' => 'composer']], $entries[0]->content['composers']);

        $this->assertSame(EntryType::VIEW, $entries[1]->type);
        $this->assertSame('tests::partials.links', $entries[1]->content['name']);
        $this->assertSame($this->viewsDirectory.'/partials/links.blade.php', $entries[1]->content['path']);
        $this->assertSame(['bar'], $entries[1]->content['data']);
        $this->assertArrayNotHasKey('composers', $entries[1]->content);

        $this->assertSame(EntryType::VIEW, $entries[2]->type);
        $this->assertSame('tests::layouts.master', $entries[2]->content['name']);
        $this->assertSame($this->viewsDirectory.'/layouts/master.blade.php', $entries[2]->content['path']);
        $this->assertSame(['bar', 'test', 'foo'], $entries[2]->content['data']);
        $this->assertCount(2, $entries[2]->content['composers']);
        $this->assertStringStartsWith('Closure at', $entries[2]->content['composers'][0]['name']);
        $this->assertStringContainsString('ViewWatcherTest', $entries[2]->content['composers'][0]['name']);
        $this->assertSame('composer', $entries[2]->content['composers'][0]['type']);
        $this->assertStringStartsWith('Closure at', $entries[2]->content['composers'][1]['name']);
        $this->assertStringContainsString('ViewWatcherTest', $entries[2]->content['composers'][1]['name']);
        $this->assertSame('creator', $entries[2]->content['composers'][1]['type']);
    }
}


class ViewComposer
{
    public function compose(\Illuminate\View\View $view)
    {
        $view->with('bar', 'baz');
    }
}

