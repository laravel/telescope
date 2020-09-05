<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Support\Facades\Mail;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\MailWatcher;

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
        Mail::raw('Telescope is amazing!', function ($message) {
            $message->from('from@laravel.com')
                ->to('to@laravel.com')
                ->cc(['cc1@laravel.com', 'cc2@laravel.com'])
                ->bcc('bcc@laravel.com')
                ->subject('Check this out!');
        });

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::MAIL, $entry->type);
        $this->assertEmpty($entry->content['mailable']);
        $this->assertFalse($entry->content['queued']);
        $this->assertSame(['from@laravel.com'], array_keys($entry->content['from']));
        $this->assertSame(['to@laravel.com'], array_keys($entry->content['to']));
        $this->assertSame(['cc1@laravel.com', 'cc2@laravel.com'], array_keys($entry->content['cc']));
        $this->assertSame(['bcc@laravel.com'], array_keys($entry->content['bcc']));
        $this->assertSame('Check this out!', $entry->content['subject']);
        $this->assertSame('Telescope is amazing!', $entry->content['html']);
        $this->assertStringContainsString('Telescope is amazing!', $entry->content['raw']);
        $this->assertEmpty($entry->content['replyTo']);
    }
}
