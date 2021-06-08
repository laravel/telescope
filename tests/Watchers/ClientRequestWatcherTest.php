<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\ClientRequestWatcher;

class ClientRequestWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        if (! class_exists(\GuzzleHttp\Client::class)) {
            $this->markTestSkipped('The "guzzlehttp/guzzle" composer package is required for this test.');
        }

        $app->get('config')->set('telescope.watchers', [
            ClientRequestWatcher::class => true,
        ]);
    }

    public function test_client_request_watcher_registers_succesful_client_request_and_response()
    {
        Http::fake([
            '*' => Http::response(['foo' => 'bar'], 201, ['Content-Type' => 'application/json', 'Cache-Control' => 'no-cache,private']),
        ]);

        Http::withHeaders(['Accept-Language' => 'nl_BE'])->get('https://laravel.com/foo/bar');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNotNull($entry);
        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('GET', $entry->content['method']);
        $this->assertSame('https://laravel.com/foo/bar', $entry->content['uri']);
        $this->assertNotNull($entry->content['headers']);
        $this->assertSame('nl_BE', $entry->content['headers']['accept-language']);
        $this->assertSame(201, $entry->content['response_status']);
        $this->assertSame(['content-type' => 'application/json', 'cache-control' => 'no-cache,private'], $entry->content['response_headers']);
        $this->assertSame(['foo' => 'bar'], $entry->content['response']);
    }

    public function test_client_request_watcher_registers_redirect_response()
    {
        Http::fake([
            '*' => Http::response(null, 301, ['Location' => 'https://foo.bar']),
        ]);

        Http::withoutRedirecting()->get('https://laravel.com');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNotNull($entry);
        $this->assertEquals('Redirected to https://foo.bar', $entry->content['response']);
    }

    public function test_client_request_watcher_plain_text_response()
    {
        Http::fake([
            '*' => Http::response('plain telescope response', 200, ['Content-Type' => 'text/plain']),
        ]);

        Http::get('https://laravel.com/fake-plain-text');


        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('GET', $entry->content['method']);
        $this->assertSame(200, $entry->content['response_status']);
        $this->assertSame('plain telescope response', $entry->content['response']);
    }

    public function test_client_request_watcher_registers_server_error_response()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Something went wrong!'], 500),
        ]);

        Http::get('https://laravel.com');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNotNull($entry);
        $this->assertEquals(['error' => 'Something went wrong!'], $entry->content['response']);
    }

    public function test_client_request_watcher_hides_password()
    {
        Http::fake([
            '*' => Http::response(null, 204),
        ]);

        Http::post('https://laravel.com/auth', [
            'email' => 'telescope@laravel.com',
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame('telescope@laravel.com', $entry->content['payload']['email']);
        $this->assertSame('********', $entry->content['payload']['password']);
        $this->assertSame('********', $entry->content['payload']['password_confirmation']);
    }

    public function test_client_request_watcher_hides_authorization()
    {
        Http::fake([
            '*' => Http::response(null, 204),
        ]);

        Http::withHeaders([
            'Authorization' => 'Basic YWxhZGRpbjpvcGVuc2VzYW1l',
            'Content-Type' => 'application/json',
        ])->post('https://laravel.com/dashboard');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame('application/json', $entry->content['headers']['content-type']);
        $this->assertSame('********', $entry->content['headers']['authorization']);
    }

    public function test_client_request_watcher_hides_php_auth_pw()
    {
        Http::fake([
            '*' => Http::response(null, 204),
        ]);

        Http::withHeaders([
            'php-auth-pw' => 'secret',
        ])->post('https://laravel.com/dashboard');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame('********', $entry->content['headers']['php-auth-pw']);
    }

    public function test_client_request_watcher_handles_form_request()
    {
        Http::fake([
            '*' => Http::response(null, 204),
        ]);

        Http::asForm()->post('https://laravel.com/form-route', ['firstname' => 'Taylor', 'lastname' => 'Otwell']);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame(['firstname' => 'Taylor', 'lastname' => 'Otwell'], $entry->content['payload']);
    }

    public function test_client_request_watcher_handles_multipart_request()
    {
        Http::fake([
            '*' => Http::response(null, 204),
        ]);

        Http::asMultipart()->post('https://laravel.com/multipart-route', ['firstname' => 'Taylor', 'lastname' => 'Otwell']);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame(['firstname' => 'Taylor', 'lastname' => 'Otwell'], $entry->content['payload']);
    }

    public function test_client_request_watcher_handles_file_uploads()
    {
        Http::fake([
            '*' => Http::response(null, 204),
        ]);

        $image = UploadedFile::fake()->image('avatar.jpg');

        Http::attach(
            'image', file_get_contents($image), 'photo.jpg', ['foo' => 'bar']
        )->post('https://laravel.com/fake-upload-file-route');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CLIENT_REQUEST, $entry->type);
        $this->assertSame('POST', $entry->content['method']);
        $this->assertSame('photo.jpg', $entry->content['payload']['image']['name']);
        $this->assertSame(($image->getSize() / 1000) .'KB', $entry->content['payload']['image']['size']);
        $this->assertSame(['foo' => 'bar'], $entry->content['payload']['image']['headers']);
    }
}
