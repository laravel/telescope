<?php

namespace Laravel\Telescope\Tests\Http;
use Laravel\Telescope\Tests\FeatureTestCase;
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
}
