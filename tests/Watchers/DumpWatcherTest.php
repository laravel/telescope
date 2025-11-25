<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Contracts\Cache\Repository;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\DumpWatcher;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('telescope.watchers', [
    DumpWatcher::class => true,
])]
class DumpWatcherTest extends FeatureTestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app->make(Repository::class)->forever('telescope:dump-watcher', true);
    }

    public function test_dump_watcher_register_entry()
    {
        $var = 'Telescopes are better than binoculars';
        dump($var);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::DUMP, $entry->type);
        $this->assertStringContainsString($var, $entry->content['dump']);
    }
}
