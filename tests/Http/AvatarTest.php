<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\LogWatcher;
use Orchestra\Testbench\Attributes\WithConfig;
use Psr\Log\LoggerInterface;

#[WithConfig('logging.default', 'syslog')]
#[WithConfig('telescope.watchers', [
    LogWatcher::class => true,
])]
class AvatarTest extends FeatureTestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Authorize::class);
    }

    public function test_it_can_generate_avatar_url()
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
                            'avatar' => 'https://www.gravatar.com/avatar/'.md5(Str::lower($user['email'])).'?s=200',
                        ],
                    ],
                ],
            ]);
    }

    public function test_it_can_register_custom_avatar_path()
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

    public function test_it_can_read_custom_avatar_path_on_null_email()
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
        $user->email = null;

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

    public function getAuthPasswordName()
    {
        return 'passord name';
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
