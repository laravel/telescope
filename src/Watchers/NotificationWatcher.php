<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;

class NotificationWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(NotificationSent::class, [$this, 'recordANewNotification']);
    }

    /**
     * Record a new notification message was sent.
     *
     * @param \Illuminate\Notifications\Events\NotificationSent $event
     * @return void
     */
    public function recordANewNotification(NotificationSent $event)
    {
        Telescope::record(3, [
            'channel' => $event->channel,
            'response' => $event->response,
            'notifiable' => $this->formatNotifiable($event->notifiable),
            'notification' => $this->formatNotification($event->notification),
        ]);
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
        return get_class($notification).':'.$notification->id;
    }
}