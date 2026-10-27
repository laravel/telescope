<?php

namespace Laravel\Telescope\Tests\Http;
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
}
