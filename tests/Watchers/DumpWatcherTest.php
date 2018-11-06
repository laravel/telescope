<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Illuminate\Contracts\Cache\Repository;
use Laravel\Telescope\Watchers\DumpWatcher;
use Laravel\Telescope\Tests\FeatureTestCase;

class DumpWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Repository::class)->forever('telescope:dump-watcher', true);

        $app->get('config')->set('telescope.watchers', [
            DumpWatcher::class => true,
        ]);
    }

    public function test_dump_watcher_register_entry()
    {
        $var = 'Telescopes are better than binoculars';
        dump($var);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::DUMP, $entry->type);
        $this->assertContains($var, $entry->content['dump']);
    }
}
