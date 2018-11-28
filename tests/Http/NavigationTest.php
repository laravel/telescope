<?php

namespace Laravel\Telescope\Tests\Unit;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\JobWatcher;
use Laravel\Telescope\Watchers\LogWatcher;
use Laravel\Telescope\Watchers\DumpWatcher;
use Laravel\Telescope\Watchers\MailWatcher;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\CacheWatcher;
use Laravel\Telescope\Watchers\EventWatcher;
use Laravel\Telescope\Watchers\ModelWatcher;
use Laravel\Telescope\Watchers\QueryWatcher;
use Laravel\Telescope\Watchers\RedisWatcher;
use Laravel\Telescope\Watchers\CommandWatcher;
use Laravel\Telescope\Watchers\RequestWatcher;
use Laravel\Telescope\Watchers\ScheduleWatcher;
use Laravel\Telescope\Watchers\ExceptionWatcher;
use Laravel\Telescope\Watchers\NotificationWatcher;

class NavigationTest extends FeatureTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function telescopeIndexRoutesProvider()
    {
        return [
            'Mail' => ['Mail', MailWatcher::class],
            'Exceptions' => ['Exception', ExceptionWatcher::class],
            'Dumps' => ['Dump', DumpWatcher::class],
            'Logs' => ['Log', LogWatcher::class],
            'Notifications' => ['Notification', NotificationWatcher::class],
            'Jobs' => ['Job', JobWatcher::class],
            'Events' => ['Event', EventWatcher::class],
            'Cache' => ['Cache', CacheWatcher::class],
            'Queries' => ['Query', QueryWatcher::class],
            'Models' => ['Model', ModelWatcher::class],
            'Request' => ['Request', RequestWatcher::class],
            'Commands' => ['Command', CommandWatcher::class],
            'Schedule' => ['Schedule', ScheduleWatcher::class],
            'Redis' => ['Redis', RedisWatcher::class],
        ];
    }

    /**
     * @dataProvider telescopeIndexRoutesProvider
     */
    public function test_navigation_visible($label, $watcher)
    {
        $this->app->get('config')->set(
            'telescope.watchers', [
            $watcher => true,
        ]);

        $this->assertContains($label, Telescope::navigation());
    }

    /**
     * @dataProvider telescopeIndexRoutesProvider
     */
    public function test_navigation_hidden($label, $watcher)
    {
        $this->app->get('config')->set(
            'telescope.watchers', [
            $watcher => false,
        ]);

        $this->assertNotContains($label, Telescope::navigation());
    }
}
