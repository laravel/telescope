<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;

class NotificationsWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(NotificationSent::class, [$this, 'recordNotification']);
    }

    /**
     * Record a new notification message was sent.
     *
     * @param \Illuminate\Notifications\Events\NotificationSent $event
     * @return void
     */
    public function recordNotification(NotificationSent $event)
    {
        Telescope::recordNotification(IncomingEntry::make([
            'channel' => $event->channel,
            'response' => $event->response,
            'notifiable' => $this->formatNotifiable($event->notifiable),
            'notification' => $this->formatNotification($event->notification),
        ])->withTags($this->extractTagsFromEvent($event)));
    }

    /**
     * Format the given notifiable.
     *
     * @param  mixed $notifiable
     * @return string
     */
    private function formatNotifiable($notifiable)
    {
        if ($notifiable instanceof Model) {
            return get_class($notifiable).':'.$notifiable->getKey();
        }

        return $notifiable;
    }

    /**
     * Format the given notification.
     *
     * @param  \Illuminate\Notifications\Notification $notification
     * @return string
     */
    private function formatNotification($notification)
    {
        return get_class($notification);
    }

    /**
     * Extract tags from the given event.
     *
     * @param  \Illuminate\Notifications\Events\NotificationSent $event
     * @return array
     */
    private function extractTagsFromEvent($event)
    {
        return [$this->formatNotification($event->notification), $this->formatNotifiable($event->notifiable)];
    }
}
