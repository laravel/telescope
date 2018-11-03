<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Illuminate\Support\Facades\Mail;
use Laravel\Telescope\Watchers\MailWatcher;
use Laravel\Telescope\Tests\FeatureTestCase;

class MailWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            MailWatcher::class => true,
        ]);

        $app->get('config')->set('mail.driver', 'array');
    }

    public function test_mail_watcher_registers_entry()
    {
        
        Mail::raw('Telescope is amazing!', function($message) {
            $message->from('from@laravel.com')
                ->to('to@laravel.com')
                ->cc(['cc1@laravel.com', 'cc2@laravel.com'])
                ->bcc('bcc@laravel.com')
                ->subject('Check this out!');
        });

        
        $entry = $this->loadTelescopeEntries()->first();

        self::assertSame(EntryType::MAIL, $entry->type);
        self::assertEmpty($entry->content['mailable']);
        self::assertSame(false, $entry->content['queued']);
        self::assertSame(['from@laravel.com'], array_keys($entry->content['from']));
        self::assertSame(['to@laravel.com'], array_keys($entry->content['to']));
        self::assertSame(['cc1@laravel.com', 'cc2@laravel.com'], array_keys($entry->content['cc']));
        self::assertSame(['bcc@laravel.com'], array_keys($entry->content['bcc']));
        self::assertSame('Check this out!', $entry->content['subject']);
        self::assertSame('Telescope is amazing!', $entry->content['html']);
        self::assertContains('Telescope is amazing!', $entry->content['raw']);
        self::assertEmpty($entry->content['replyTo']);
    }
}
