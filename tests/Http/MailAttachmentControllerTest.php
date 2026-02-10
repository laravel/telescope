<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Support\Facades\Mail;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\MailWatcher;
use Orchestra\Testbench\Attributes\WithConfig;

#[WithConfig('mail.driver', 'array')]
#[WithConfig('telescope.watchers', [
    MailWatcher::class => true,
])]
class MailAttachmentControllerTest extends FeatureTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Authorize::class);
    }

    public function test_download_attachment()
    {
        Mail::raw('Telescope is amazing!', function ($message) {
            $message->from('from@laravel.com')
                ->to('to@laravel.com')
                ->subject('Check this out!')
                ->attachData('attachment content', 'document.pdf', [
                    'mime' => 'application/pdf',
                ]);
        });

        $entry = $this->loadTelescopeEntries()->first();

        $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/0');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Length', strlen('attachment content'));
        $this->assertSame('attachment content', $response->content());
    }

    public function test_returns_404_for_invalid_attachment_index()
    {
        Mail::raw('Telescope is amazing!', function ($message) {
            $message->from('from@laravel.com')
                ->to('to@laravel.com')
                ->subject('Check this out!')
                ->attachData('attachment content', 'document.pdf', [
                    'mime' => 'application/pdf',
                ]);
        });

        $entry = $this->loadTelescopeEntries()->first();

        $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/5');

        $response->assertNotFound();
    }

    public function test_returns_404_for_entry_without_attachments()
    {
        Mail::raw('Telescope is amazing!', function ($message) {
            $message->from('from@laravel.com')
                ->to('to@laravel.com')
                ->subject('Check this out!');
        });

        $entry = $this->loadTelescopeEntries()->first();

        $response = $this->get('/telescope/telescope-api/mail/'.$entry->uuid.'/attachments/0');

        $response->assertNotFound();
    }
}
