<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Http\Request;
use Laravel\Telescope\Telescope;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Tests\FeatureTestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Orchestra\Testbench\Http\Middleware\VerifyCsrfToken;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class AuthorizationTest extends FeatureTestCase
{
    protected function getPackageProviders($app)
    {
        return array_merge(
            parent::getPackageProviders($app),
            [TelescopeApplicationServiceProvider::class]
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([VerifyCsrfToken::class]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Telescope::auth(null);
    }

    public function test_clean_telescope_installation_denies_access_by_default()
    {
        $this->post('/telescope/telescope-api/requests')
            ->assertStatus(403);
    }

    public function test_clean_telescope_installation_denies_access_by_default_for_any_auth_user()
    {
        $this->actingAs(new Authenticated);

        $this->post('/telescope/telescope-api/requests')
            ->assertStatus(403);
    }

    public function test_guests_gets_unauthorized_by_gate()
    {
        Telescope::auth(function (Request $request) {
            return Gate::check('viewTelescope', [$request->user()]);
        });

        Gate::define('viewTelescope', function ($user) {
            return true;
        });

        $this->post('/telescope/telescope-api/requests')
            ->assertStatus(403);
    }

    public function test_authenticated_user_gets_authorized_by_gate()
    {
        $this->actingAs(new Authenticated);

        Telescope::auth(function (Request $request) {
            return Gate::check('viewTelescope', [$request->user()]);
        });

        Gate::define('viewTelescope', function (Authenticatable $user) {
            return $user->getAuthIdentifier() === 'telescope-test';
        });

        $this->post('/telescope/telescope-api/requests')
            ->assertStatus(200);
    }

    public function test_guests_can_be_authorized()
    {
        Telescope::auth(function (Request $request) {
            return Gate::check('viewTelescope', [$request->user()]);
        });

        Gate::define('viewTelescope', function (?Authenticatable $user) {
            return true;
        });

        $this->post('/telescope/telescope-api/requests')
            ->assertStatus(200);
    }

    public function test_unauthorized_requests()
    {
        Telescope::auth(function () {
            return false;
        });

        $this->get('/telescope/telescope-api/requests')
            ->assertStatus(403);
    }

    public function test_authorized_requests()
    {
        Telescope::auth(function () {
            return true;
        });

        $this->post('/telescope/telescope-api/requests')
            ->assertSuccessful();
    }
}

class Authenticated implements Authenticatable
{
    public $email;

    public function getAuthIdentifierName()
    {
        return 'Telescope Test';
    }

    public function getAuthIdentifier()
    {
        return 'telescope-test';
    }

    public function getAuthPassword()
    {
        return 'secret';
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
