<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Support\Collection;
use Laravel\Telescope\Storage\EntryModel;
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
                'slow' => 0.1,
            ],
        ]);
    }

    public function test_query_watcher_registers_database_queries()
    {
        $this->prepareDatabase();

        $this->app->get('db')->table('users')->count();

        $this->terminateTelescope();

        $entry = EntryModel::query()->first();

        self::assertSame('query', $entry->type);
        self::assertSame('select count(*) as aggregate from "users"', $entry->content['sql']);
        self::assertSame('testbench', $entry->content['connection']);
        self::assertFalse($entry->content['slow']);
    }

    public function test_query_watcher_can_tag_slow_queries()
    {
        $this->prepareDatabase();

        $records = Collection::times(100, function ($count) {
            return [
                'name' => 'Laravel',
                'email' => $count . '@laravel.com',
                'password' => 'secret',
            ];
        });
        $this->app->get('db')->table('users')->insert($records->toArray());

        $this->terminateTelescope();

        $entry = EntryModel::query()->first();

        self::assertSame('query', $entry->type);
        self::assertCount(300, $entry->content['bindings']);
        self::assertSame('testbench', $entry->content['connection']);
        self::assertTrue($entry->content['slow']);
    }
}
