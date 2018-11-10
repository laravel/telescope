<?php

namespace Laravel\Telescope\Tests\Fixtures;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class BoomerangNotification extends BaseNotification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)->greeting('Throw a boomerang')->line('They are fun!');
    }
}
