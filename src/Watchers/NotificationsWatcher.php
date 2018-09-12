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
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(NotificationSent::class, [$this, 'recordNotification']);
    }

    /**
     * Record a new notification message was sent.
     *
     * @param  \Illuminate\Notifications\Events\NotificationSent  $event
     * @return void
     */
    public function recordNotification(NotificationSent $event)
    {
        Telescope::recordNotification(IncomingEntry::make([
            'notification' => get_class($event->notification),
            'notifiable' => $this->formatNotifiable($event->notifiable),
            'channel' => $event->channel,
            'response' => $event->response,
        ])->tags($this->tags($event)));
    }

    /**
     * Extract the tags for the given event.
     *
     * @param  \Illuminate\Notifications\Events\NotificationSent  $event
     * @return array
     */
    private function tags($event)
    {
        return [
            get_class($event->notification),
            $this->formatNotifiable($event->notifiable)
        ];
    }

    /**
     * Format the given notifiable into a tag.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    private function formatNotifiable($notifiable)
    {
        return $notifiable instanceof Model
                ? get_class($notifiable).':'.$notifiable->getKey()
                : $notifiable;
    }
}
