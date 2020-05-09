<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\LogWatcher;
use Psr\Log\LoggerInterface;

class AvatarTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Authorize::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('logging.default', 'syslog');

        $app->get('config')->set('telescope.watchers', [
            LogWatcher::class => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_register_custom_avatar_path()
    {
        $user = null;

        Telescope::withoutRecording(function () use (&$user) {
            $this->loadLaravelMigrations();

            $user = UserEloquent::create([
                'id' => 1,
                'name' => 'Telescope',
                'email' => 'telescope@laravel.com',
                'password' => 'secret',
            ]);
        });

        $this->app->get('config')->set('telescope.avatar_driver', 'custom');

        Telescope::avatar(function ($id) {
            return "/images/{$id}.jpg";
        });

        $this->actingAs($user);

        $this->app->get(LoggerInterface::class)->error('Avatar path will be generated.', [
            'exception' => 'Some error message',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->get("/telescope/telescope-api/logs/{$entry->uuid}")
            ->assertOk()
            ->assertJson([
                'entry' => [
                    'content' => [
                        'user' => [
                            'avatar' => '/images/1.jpg',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_will_not_register_custom_avatar_path_when_not_configured()
    {
        $user = null;

        Telescope::withoutRecording(function () use (&$user) {
            $this->loadLaravelMigrations();

            $user = UserEloquent::create([
                'id' => 1,
                'name' => 'Telescope',
                'email' => 'telescope@laravel.com',
                'password' => 'secret',
            ]);
        });

        Telescope::avatar(function ($id) {
            return "/images/{$id}.jpg";
        });

        $this->actingAs($user);

        $this->app->get(LoggerInterface::class)->error('Avatar path will default to Gravatar.', [
            'exception' => 'Some error message',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->get("/telescope/telescope-api/logs/{$entry->uuid}")
            ->assertOk()
            ->assertJson([
                'entry' => [
                    'content' => [
                        'user' => [
                            'avatar' => 'https://www.gravatar.com/avatar/dac001a0dfeebe3b320cefa9f3a7d813?s=200',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_will_not_set_avatar_path_when_the_configuration_is_empty()
    {
        $user = null;

        Telescope::withoutRecording(function () use (&$user) {
            $this->loadLaravelMigrations();

            $user = UserEloquent::create([
                'id' => 1,
                'name' => 'Telescope',
                'email' => 'telescope@laravel.com',
                'password' => 'secret',
            ]);
        });

        $this->app->get('config')->set('telescope.avatar_driver', '');

        Telescope::avatar(function ($id) {
            return "/images/{$id}.jpg";
        });

        $this->actingAs($user);

        $this->app->get(LoggerInterface::class)->error('Avatar path will not be generated.', [
            'exception' => 'Some error message',
        ]);

        $entry = $this->loadTelescopeEntries()->first();

        $json = $this->get("/telescope/telescope-api/logs/{$entry->uuid}")
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('avatar', $json['entry']['content']['user']);
    }
}

class UserEloquent extends Model implements Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public function getAuthIdentifierName()
    {
        return $this->email;
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return 'i-am-telescope';
    }

    public function setRememberToken($value)
    {
        //
    }

    public function getRememberTokenName()
    {
        //
    }
}
