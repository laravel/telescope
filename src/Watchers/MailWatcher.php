<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Illuminate\Mail\Events\MessageSent;

class MailWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(MessageSent::class, [$this, 'recordANewMail']);
    }

    /**
     * Record a new mail message was sent.
     *
     * @param \Illuminate\Mail\Events\MessageSent $event
     * @return void
     */
    public function recordANewMail(MessageSent $event)
    {
        Telescope::record(1, [
            'from' => $event->message->getFrom(),
            'to' => $event->message->getTo(),
            'replyTo' => $event->message->getReplyTo(),
            'time' => $event->message->getDate()->format('Y-m-d H:i:s'),
            'cc' => $event->message->getCc(),
            'bcc' => $event->message->getBcc(),
            'subject' => $event->message->getSubject(),
            'html' => $event->message->getBody(),
            'raw' => $event->message->toString(),
        ], $this->extractTagsFromMessage($event->message));
    }

    /**
     * Extract tags from the given message.
     *
     * @param  \Swift_Message $message
     * return array
     */
    private function extractTagsFromMessage($message)
    {
        return array_merge(
            array_keys($message->getTo() ?: []), array_keys($message->getCc() ?: []),
            array_keys($message->getBcc() ?: []), array_keys($message->getFrom() ?: [])
        );
    }
}