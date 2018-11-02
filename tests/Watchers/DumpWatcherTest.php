<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Contracts\Cache\Repository;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\DumpWatcher;

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

        self::assertSame(EntryType::DUMP, $entry->type);
        self::assertContains($var, $entry->content['dump']);
    }
}
