<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Redis;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\RedisWatcher;
use Mockery;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('database.redis.client', 'phpredis')]
#[WithConfig('telescope.watchers', [
    RedisWatcher::class => true,
])]
class RedisWatcherTest extends FeatureTestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $this->markTestSkippedUnless(extension_loaded('redis'), 'The phpredis extension is required for this test.');

        parent::defineEnvironment($app);

        $app->get('config')->set('database.redis.client', 'phpredis');

        $app['redis']->enableEvents();
    }

    public function test_redis_watcher_registers_entries()
    {
        Redis::connection('default')->get('telescope:test');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::REDIS, $entry->type);
        $this->assertSame('get telescope:test', $entry->content['command']);
        $this->assertSame('default', $entry->content['connection']);
    }

    public function test_does_not_register_when_redis_unbound()
    {
        $app = Mockery::mock(Application::class);

        $app->makePartial();

        $app->expects('bound')
            ->with('redis')
            ->andReturn(false);

        $app->shouldNotReceive('make')
            ->with('redis');

        $watcher = new RedisWatcher([]);

        $watcher->register($app);
    }
}
