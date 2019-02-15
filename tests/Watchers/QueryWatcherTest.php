<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Illuminate\Support\Collection;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\QueryWatcher;

class QueryWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            QueryWatcher::class => [
                'enabled' => true,
                'slow' => 0.2,
            ],
        ]);
    }

    public function test_query_watcher_registers_database_queries()
    {
        $this->app->get('db')->table('telescope_entries')->count();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::QUERY, $entry->type);
        $this->assertSame('select count(*) as aggregate from "telescope_entries"', $entry->content['sql']);
        $this->assertSame('testbench', $entry->content['connection']);
        $this->assertFalse($entry->content['slow']);
    }

    public function test_query_watcher_can_tag_slow_queries()
    {
        $records = Collection::times(300, function () {
            return [
                'tag' => Str::random(),
            ];
        });

        $this->app->get('db')->table('telescope_monitoring')->insert($records->toArray());

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::QUERY, $entry->type);
        $this->assertCount(300, $entry->content['bindings']);
        $this->assertSame('testbench', $entry->content['connection']);
        $this->assertTrue($entry->content['slow']);
    }
}
