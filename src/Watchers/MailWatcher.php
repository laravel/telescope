<?php

namespace Laravel\Telescope\Watchers;

use Swift_Message;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;

class MailWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register(Application $app)
    {
        $app->make(Dispatcher::class)->listen(MessageSent::class, [$this, 'recordMail']);
    }

    /**
     * Record a mail message was sent.
     *
     * @param  \Illuminate\Mail\Events\MessageSent  $event
     * @return void
     */
    public function recordMail(MessageSent $event)
    {
        Telescope::recordMail(IncomingEntry::make([
            'mailable' => $this->getMailable($event),
            'queued' => $this->getQueuedStatus($event),
            'from' => $event->message->getFrom(),
            'replyTo' => $event->message->getReplyTo(),
            'to' => $event->message->getTo(),
            'cc' => $event->message->getCc(),
            'bcc' => $event->message->getBcc(),
            'subject' => $event->message->getSubject(),
            'html' => $event->message->getBody(),
            'raw' => $event->message->toString(),
        ])->tags($this->tags($event->message, $event->data)));
    }

    /**
     * Get the name of the mailable.
     *
     * @param  \Illuminate\Mail\Events\MessageSent  $event
     * @return string
     */
    protected function getMailable(MessageSent $event)
    {
        if (isset($event->data['__laravel_notification'])) {
            return $event->data['__laravel_notification'];
        }

        return $event->data['__telescope_mailable'] ?? '';
    }

    /**
     * Determine whether the mailable was queued.
     *
     * @param  \Illuminate\Mail\Events\MessageSent  $event
     * @return bool
     */
    protected function getQueuedStatus(MessageSent $event)
    {
        if (isset($event->data['__laravel_notification_queued'])) {
            return $event->data['__laravel_notification_queued'];
        }

        return $event->data['__telescope_queued'] ?? false;
    }

    /**
     * Extract the tags from the message.
     *
     * @param  \Swift_Message  $message
     * @param  array  $data
     * @return array
     */
    private function tags(Swift_Message $message, $data)
    {
        return array_merge(
            array_keys($message->getTo() ?: []),
            array_keys($message->getCc() ?: []),
            array_keys($message->getBcc() ?: []),
            $data['__telescope'] ?? []
        );
    }
}
