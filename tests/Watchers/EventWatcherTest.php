<?php

namespace Laravel\Telescope\Tests\Watchers;

use Dummies\DummyEvent;
use Dummies\DummyEventListener;
use Dummies\DummyEventSubscriber;
use Dummies\DummyEventWithObject;
use Dummies\DummyInvokableEventListener;
use Dummies\DummyObject;
use Dummies\IgnoredEvent;
use Illuminate\Support\Facades\Event;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\EventWatcher;

class EventWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            EventWatcher::class => [
                'enabled' => true,
                'ignore' => [
                    IgnoredEvent::class,
                ],
            ],
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
        $this->assertContains('Telescope', $entry->content['payload']['data']);
        $this->assertContains('Laravel', $entry->content['payload']['data']);
        $this->assertContains('PHP', $entry->content['payload']['data']);
    }

    public function test_event_watcher_with_object_property_calls_format_for_telescope_method_if_it_exists()
    {
        Event::listen(DummyEventWithObject::class, function ($payload) {
            //
        });

        event(new DummyEventWithObject());

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EVENT, $entry->type);
        $this->assertSame(DummyEventWithObject::class, $entry->content['name']);
        $this->assertArrayHasKey('thing', $entry->content['payload']);
        $this->assertSame(DummyObject::class, $entry->content['payload']['thing']['class']);
        $this->assertContains('Telescope', $entry->content['payload']['thing']['properties']);
        $this->assertContains('Laravel', $entry->content['payload']['thing']['properties']);
        $this->assertContains('PHP', $entry->content['payload']['thing']['properties']);
    }

    public function test_event_watcher_registers_events_and_stores_payloads_with_subscriber_methods()
    {
        Event::listen(DummyEvent::class, DummyEventSubscriber::class.'@handleDummyEvent');

        event(new DummyEvent('Telescope', 'Laravel', 'PHP'));

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EVENT, $entry->type);
        $this->assertSame(DummyEvent::class, $entry->content['name']);
        $this->assertArrayHasKey('data', $entry->content['payload']);
        $this->assertContains('Telescope', $entry->content['payload']['data']);
        $this->assertContains('Laravel', $entry->content['payload']['data']);
        $this->assertContains('PHP', $entry->content['payload']['data']);
    }

    public function test_event_watcher_registers_events_and_stores_payloads_with_subscriber_classes()
    {
        Event::listen(DummyEvent::class, [DummyEventSubscriber::class, 'handleDummyEvent']);

        event(new DummyEvent('Telescope', 'Laravel', 'PHP'));

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::EVENT, $entry->type);
        $this->assertSame(DummyEvent::class, $entry->content['name']);
        $this->assertArrayHasKey('data', $entry->content['payload']);
        $this->assertContains('Telescope', $entry->content['payload']['data']);
        $this->assertContains('Laravel', $entry->content['payload']['data']);
        $this->assertContains('PHP', $entry->content['payload']['data']);
    }

    public function test_event_watcher_ignore_event()
    {
        event(new IgnoredEvent());

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNull($entry);
    }

    /**
     * @dataProvider formatListenersProvider
     */
    public function test_format_listeners($listener, $formatted)
    {
        Event::listen(DummyEvent::class, $listener);

        $method = new \ReflectionMethod(EventWatcher::class, 'formatListeners');
        $method->setAccessible(true);

        $this->assertSame($formatted, $method->invoke(new EventWatcher, DummyEvent::class)[0]['name']);
    }

    public static function formatListenersProvider()
    {
        return [
            'class string' => [
                DummyEventListener::class,
                DummyEventListener::class.'@handle',
            ],
            'class string with method' => [
                DummyEventListener::class.'@handle',
                DummyEventListener::class.'@handle',
            ],
            'array class string and method' => [
                [DummyEventListener::class, 'handle'],
                DummyEventListener::class.'@handle',
            ],
            'array object and method' => [
                [new DummyEventListener, 'handle'],
                DummyEventListener::class.'@handle',
            ],
            'callable object' => [
                new DummyInvokableEventListener,
                DummyInvokableEventListener::class.'@__invoke',
            ],
            'anonymous callable object' => [
                $class = new class
                {
                    public function __invoke()
                    {
                        //
                    }
                },
                get_class($class).'@__invoke',
            ],
            'closure' => [
                function () {
                    //
                },
                sprintf('Closure at %s[%s:%s]', __FILE__, __LINE__ - 3, __LINE__ - 1),
            ],
        ];
    }
}

namespace Dummies;

class DummyEvent
{
    public $data;

    public function __construct(...$payload)
    {
        $this->data = $payload;
    }

    public function handle()
    {
        //
    }
}

class DummyEventWithObject
{
    public $thing;

    public function __construct()
    {
        $this->thing = new DummyObject;
    }
}

class DummyObject
{
    public function formatForTelescope(): array
    {
        return [
            'Telescope', 'Laravel', 'PHP',
        ];
    }
}

class DummyEventSubscriber
{
    public function handleDummyEvent($event)
    {
        //
    }
}

class IgnoredEvent
{
    public function handle()
    {
        //
    }
}

class DummyEventListener
{
    public function handle($event)
    {
        //
    }
}

class DummyInvokableEventListener
{
    public function __invoke($event)
    {
        //
    }
}
