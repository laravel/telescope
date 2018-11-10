<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Illuminate\Support\Facades\Notification;
use Laravel\Telescope\Watchers\NotificationWatcher;
use Laravel\Telescope\Tests\Fixtures\BoomerangNotification;

class NotificationWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            NotificationWatcher::class => true,
        ]);

        $app->get('config')->set('mail.driver', 'array');
    }

    public function test_notification_watcher_registers_entry()
    {
        Notification::route('mail', 'telescope@laravel.com')
                    ->notify(new BoomerangNotification);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::NOTIFICATION, $entry->type);
        $this->assertSame(BoomerangNotification::class, $entry->content['notification']);
        $this->assertSame(false, $entry->content['queued']);
        $this->assertContains('telescope@laravel.com', $entry->content['notifiable']);
        $this->assertSame('mail', $entry->content['channel']);
        $this->assertNull($entry->content['response']);
    }
}
