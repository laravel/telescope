<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Mail\Events\MessageSent;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\AbstractPart;

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
        if (! Telescope::isRecording()) {
            return;
        }

        $body = $event->message->getBody();

        Telescope::recordMail(IncomingEntry::make([
            'mailable' => $this->getMailable($event),
            'queued' => $this->getQueuedStatus($event),
            'from' => $this->formatAddresses($event->message->getFrom()),
            'replyTo' => $this->formatAddresses($event->message->getReplyTo()),
            'to' => $this->formatAddresses($event->message->getTo()),
            'cc' => $this->formatAddresses($event->message->getCc()),
            'bcc' => $this->formatAddresses($event->message->getBcc()),
            'subject' => $event->message->getSubject(),
            'html' => $body instanceof AbstractPart ? ($event->message->getHtmlBody() ?? $event->message->getTextBody()) : $body,
            'raw' => $event->message->toString(),
        ])->tags($this->tags($event->message, $event->data)));
    }

    /**
     * Get the name of the mailable.
     *
     * @param  \Illuminate\Mail\Events\MessageSent  $event
     * @return string
     */
    protected function getMailable($event)
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
    protected function getQueuedStatus($event)
    {
        if (isset($event->data['__laravel_notification_queued'])) {
            return $event->data['__laravel_notification_queued'];
        }

        return $event->data['__telescope_queued'] ?? false;
    }

    /**
     * Convert the given addresses into a readable format.
     *
     * @param  array|null  $addresses
     * @return array|null
     */
    protected function formatAddresses(?array $addresses)
    {
        if (is_null($addresses)) {
            return null;
        }

        return collect($addresses)->flatMap(function ($address, $key) {
            if ($address instanceof Address) {
                return [$address->getAddress() => $address->getName()];
            }

            return [$key => $address];
        })->all();
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
            array_keys($message->getTo() ?: []),
            array_keys($message->getCc() ?: []),
            array_keys($message->getBcc() ?: []),
            $data['__telescope'] ?? []
        );
    }
}
