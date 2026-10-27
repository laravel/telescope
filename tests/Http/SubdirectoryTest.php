<?php

namespace Laravel\Telescope\Tests\Http;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Laravel\Telescope\Http\Middleware\Authorize;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Orchestra\Testbench\Http\Middleware\VerifyCsrfToken;
use PHPUnit\Framework\Attributes\DataProvider;

class SubdirectoryTest extends FeatureTestCase
{
    protected $subdirectory = 'app';

    protected $path = 'telescope';

    protected $domain;

    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('telescope.subdirectory', $this->subdirectory);
        $app['config']->set('telescope.path', $this->path);
        $app['config']->set('telescope.domain', $this->domain);
    }

    /**
     * Generate an array to be used as a data provider for different subdirectory
     * and path combinations.
     *
     * @return array
     */
    public static function pathsProvider(): array
    {
        return [
            'unset' => [null, 'telescope', 'telescope'],
            'empty' => ['', 'telescope', 'telescope'],
            'slashes' => ['///', 'telescope', 'telescope'],
            'subdirectory' => ['app', 'telescope', 'app/telescope'],
            'boundary slashes' => ['/app/', '/telescope/', 'app/telescope'],
            'nested custom path' => ['/apps/admin/', '/debug/', 'apps/admin/debug'],
            'empty path' => ['app', '', 'app'],
            'root path' => ['app', '/', 'app'],
            'domain root' => ['', '', ''],
            'slash root' => ['/', '/', ''],
            'null path' => [null, null, ''],
        ];
    }

    /**
     * @dataProvider pathsProvider
     */
    #[DataProvider('pathsProvider')]
    public function test_route_prefixes($subdirectory, $path, $expected)
    {
        $this->subdirectory = $subdirectory;
        $this->path = $path;
        $this->refreshApplication();

        $this->assertSame($expected, Telescope::path());
        $this->assertSame(url($expected), route('telescope'));
        $this->assertSame($path, Telescope::scriptVariables()['path']);
        $this->assertSame($subdirectory, Telescope::scriptVariables()['subdirectory']);
        $this->assertSame(trim($expected.'/{view?}', '/'), $this->app['router']->getRoutes()->getByName('telescope')->uri());

        foreach ($this->app['router']->getRoutes() as $route) {
            if (str_starts_with($route->getActionName(), 'Laravel\\Telescope\\Http\\Controllers\\')) {
                $this->assertContains('telescope', $route->middleware());
                $this->assertSame(config('telescope.domain'), $route->getDomain());

                if ($expected !== '') {
                    $this->assertStringStartsWith($expected.'/', $route->uri());
                }
            }
        }

        foreach ([
            ['POST', 'telescope-api/requests', 'RequestsController@index'],
            ['GET', 'telescope-api/requests/entry-id', 'RequestsController@show'],
            ['GET', 'telescope-api/mail/entry-id/preview', 'MailHtmlController@show'],
            ['GET', 'telescope-api/mail/entry-id/download', 'MailEmlController@show'],
            ['GET', 'requests/entry-id', 'HomeController@index'],
        ] as [$method, $uri, $action]) {
            $route = $this->app['router']->getRoutes()->match(Request::create('/'.trim($expected.'/'.$uri, '/'), $method));

            $this->assertSame('Laravel\\Telescope\\Http\\Controllers\\'.$action, $route->getActionName());
        }
    }


    public function test_prefixed_routes_still_require_authorization()
    {
        $this->withoutMiddleware([VerifyCsrfToken::class, ValidateCsrfToken::class, PreventRequestForgery::class]);

        Telescope::auth(fn () => false);

        try {
            $this->get('/app/telescope')->assertForbidden();
            $this->post('/app/telescope/telescope-api/requests')->assertForbidden();
        } finally {
            Telescope::auth(null);
        }
    }

    public function test_prefixed_routes_preserve_the_configured_domain()
    {
        $this->domain = 'telescope.example.com';
        $this->refreshApplication();

        $this->assertSame('http://telescope.example.com/app/telescope', route('telescope'));

        $route = $this->app['router']->getRoutes()->match(Request::create('http://telescope.example.com/app/telescope'));

        $this->assertSame($this->domain, $route->getDomain());
        $this->assertContains('telescope', $route->middleware());
    }
}
