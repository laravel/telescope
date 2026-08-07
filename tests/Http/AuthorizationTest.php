<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Laravel\Telescope\Tests\FeatureTestCase;
use Orchestra\Testbench\Http\Middleware\VerifyCsrfToken;

class AuthorizationTest extends FeatureTestCase
{
    /** {@inheritdoc} */
    #[\Override]
    protected function getPackageProviders($app): array
    {
        return array_merge(
            parent::getPackageProviders($app),
            [TelescopeApplicationServiceProvider::class]
        );
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([VerifyCsrfToken::class]);
        $this->withoutMiddleware([ValidateCsrfToken::class]);
        $this->withoutMiddleware([PreventRequestForgery::class]);
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        Telescope::auth(null);
    }

    public function test_clean_telescope_installation_denies_access_by_default(): void
    {
        $this->post('/telescope/telescope-api/requests')
            ->assertStatus(403);
    }

    public function test_clean_telescope_installation_denies_access_by_default_for_any_auth_user(): void
    {
        $this->actingAs(new Authenticated);

        $this->post('/telescope/telescope-api/requests')
            ->assertStatus(403);
    }

    public function test_guests_gets_unauthorized_by_gate(): void
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

    public function test_authenticated_user_gets_authorized_by_gate(): void
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

    public function test_guests_can_be_authorized(): void
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

    public function test_unauthorized_requests(): void
    {
        Telescope::auth(function () {
            return false;
        });

        $this->get('/telescope/telescope-api/requests')
            ->assertStatus(403);
    }

    public function test_authorized_requests(): void
    {
        Telescope::auth(function () {
            return true;
        });

        $this->post('/telescope/telescope-api/requests')
            ->assertSuccessful();
    }

    public function test_telescope_auth_passes_via_remote_addr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->assertTrue(
            app()->environment('local') ||
            in_array($_SERVER['REMOTE_ADDR'] ?? null, ['127.0.0.1', '::1'])
        );
    }

}

class Authenticated implements Authenticatable
{
    public string $email = '';

    public function getAuthIdentifierName(): string
    {
        return 'Telescope Test';
    }

    public function getAuthIdentifier(): string
    {
        return 'telescope-test';
    }

    public function getAuthPassword(): string
    {
        return 'secret';
    }

    public function getAuthPasswordName(): string
    {
        return 'passord name';
    }

    public function getRememberToken(): string
    {
        return 'i-am-telescope';
    }

    public function setRememberToken($value): void {}


    public function getRememberTokenName(): string
    {
        return '';
    }
}
