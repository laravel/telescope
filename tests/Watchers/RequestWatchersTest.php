<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\RequestWatcher;

class RequestWatchersTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            RequestWatcher::class => true,
        ]);

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_request_watcher_registers_requests()
    {
        Route::get('/emails', function () {
            return ['email' => 'themsaid@laravel.com'];
        });

        $this->get('/emails')->assertSuccessful();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::REQUEST, $entry->type);
        $this->assertSame('GET', $entry->content['method']);
        $this->assertSame(200, $entry->content['response_status']);
        $this->assertSame('/emails', $entry->content['uri']);
    }

    public function test_request_watcher_registers_404()
    {
        $this->get('/whatever');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::REQUEST, $entry->type);
        $this->assertSame('GET', $entry->content['method']);
        $this->assertSame(404, $entry->content['response_status']);
        $this->assertSame('/whatever', $entry->content['uri']);
    }

    public function test_request_watcher_hides_password()
    {
        Route::post('/auth', function () {
            return response('success');
        });

        $this->post('/auth', [
            'email' => 'telescope@laravel.com',
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame('telescope@laravel.com', $entry->content['payload']['email']);
        $this->assertSame('********', $entry->content['payload']['password']);
        $this->assertSame('********', $entry->content['payload']['password_confirmation']);
    }

    public function test_request_watcher_hides_authorization()
    {
        Route::post('/dashboard', function () {
            return response('success');
        });

        $this->post('/dashboard', [], [
            'Authorization' => 'Basic YWxhZGRpbjpvcGVuc2VzYW1l',
            'Content-Type' => 'application/json',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame('application/json', $entry->content['headers']['content-type']);
        $this->assertSame('********', $entry->content['headers']['authorization']);
    }

    public function test_request_watcher_handles_file_uploads()
    {
        $image = UploadedFile::fake()->image('avatar.jpg');

        $this->post('fake-upload-file-route', [
            'image' => $image,
        ]);

        $uploadedImage = $this->loadTelescopeEntries()->first()->content['payload']['image'];

        $this->assertSame($image->getClientOriginalName(), $uploadedImage['name']);

        $this->assertSame($image->getSize() / 1000 .'KB', $uploadedImage['size']);
    }

    public function test_request_watcher_handles_unlinked_file_uploads()
    {
        $image = UploadedFile::fake()->image('unlinked-image.jpg');

        unlink($image->getPathName());

        $this->post('fake-upload-file-route', [
            'unlinked-image' => $image,
        ]);

        $uploadedImage = $this->loadTelescopeEntries()->first()->content['payload']['unlinked-image'];

        $this->assertSame($image->getClientOriginalName(), $uploadedImage['name']);

        $this->assertSame('0KB', $uploadedImage['size']);
    }
}
