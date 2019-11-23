<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\NotificationWatcher;

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
        $this->performNotificationAssertions('mail', 'telescope@laravel.com');
    }

    public function test_notification_watcher_registers_array_routes()
    {
        $this->performNotificationAssertions('mail', ['telescope@laravel.com','nestedroute@laravel.com']);
    }

    private function performNotificationAssertions($channel, $route)
    {
        Notification::route($channel, $route)
            ->notify(new BoomerangNotification);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::NOTIFICATION, $entry->type);
        $this->assertSame(BoomerangNotification::class, $entry->content['notification']);
        $this->assertSame(false, $entry->content['queued']);
        $this->assertStringContainsString(is_array($route) ? implode(',', $route) : $route, $entry->content['notifiable']);
        $this->assertSame($channel, $entry->content['channel']);
        $this->assertNull($entry->content['response']);
    }
}

class BoomerangNotification extends BaseNotification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Throw a boomerang')
            ->line('They are fun!');
    }
}
