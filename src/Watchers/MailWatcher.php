<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Mail\Events\MessageSent;

class MailWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(MessageSent::class, [$this, 'recordMail']);
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
     * Extract the tags from the message.
     *
     * @param  \Swift_Message  $message
     * @param  array  $data
     * @return array
     */
    private function tags($message, $data)
    {
        return array_merge(
            array_keys($message->getFrom() ?: []),
            array_keys($message->getTo() ?: []),
            array_keys($message->getCc() ?: []),
            array_keys($message->getBcc() ?: []),
            $data['__telescope'] ?? []
        );
    }
}
