<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Illuminate\Support\Facades\Event;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\EventWatcher;
use Laravel\Telescope\Tests\Fixtures\DummyEvent;

class EventWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            EventWatcher::class => true,
        ]);
    }

    public function test_event_watcher_registers_any_events()
    {
        Event::listen(DummyEvent::class, function ($payload) {
            //
        });

        event(new DummyEvent);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EVENT, $entry->type);
        $this->assertSame(DummyEvent::class, $entry->content['name']);
    }

    public function test_event_watcher_stores_payloads()
    {
        Event::listen(DummyEvent::class, function ($payload) {
            //
        });

        event(new DummyEvent('Telescope', 'Laravel', 'PHP'));

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EVENT, $entry->type);
        $this->assertSame(DummyEvent::class, $entry->content['name']);
        $this->assertArrayHasKey('data', $entry->content['payload']);
        $this->assertArraySubset(['Telescope', 'Laravel', 'PHP'], $entry->content['payload']['data']);
    }
}
