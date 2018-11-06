<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Illuminate\Support\Facades\Redis;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\RedisWatcher;

class RedisWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        if (! extension_loaded('redis')) {
            $this->markTestSkipped('The phpredis extension is required for this test.');
        }

        $app->get('config')->set('database.redis.client', 'phpredis');

        $app['redis']->enableEvents();

        $app->get('config')->set('telescope.watchers', [
            RedisWatcher::class => true,
        ]);
    }

    public function test_redis_watcher_registers_entries()
    {
        Redis::connection('default')->get('telescope:test');

        $entry = $this->loadTelescopeEntries()->first();

        self::assertSame(EntryType::REDIS, $entry->type);
        self::assertSame('get telescope:test', $entry->content['command']);
        self::assertSame('default', $entry->content['connection']);
    }
}
