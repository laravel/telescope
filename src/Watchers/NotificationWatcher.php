<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationSent;

class NotificationWatcher extends Watcher
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
            'queued' => in_array(ShouldQueue::class, class_implements($event->notification)),
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
        return array_merge([
            $this->formatNotifiable($event->notifiable),
        ], ExtractTags::from($event->notification));
    }

    /**
     * Format the given notifiable into a tag.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    private function formatNotifiable($notifiable)
    {
        if ($notifiable instanceof Model) {
            return get_class($notifiable) . ':' . $notifiable->getKey();
        } elseif ($notifiable instanceof AnonymousNotifiable) {
            return 'Anonymous:'.implode(',', $notifiable->routes);
        }

        return $notifiable;
    }
}
