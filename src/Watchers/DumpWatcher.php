<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\IncomingDumpEntry;
use Laravel\Telescope\Telescope;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

class DumpWatcher extends Watcher
{
    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new watcher instance.
     *
     * @param  array  $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->cache = Telescope::cacheStore();
    }

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        if (! $this->cache->get('telescope:dump-watcher')) {
            return;
        }

        $htmlDumper = new HtmlDumper();
        $htmlDumper->setDumpHeader('');

        VarDumper::setHandler(function ($var) use ($htmlDumper) {
            $this->recordDump($htmlDumper->dump(
                (new VarCloner)->cloneVar($var), true
            ));
        });
    }

    /**
     * Record a dumped variable.
     *
     * @param  string  $dump
     * @return void
     */
    public function recordDump($dump)
    {
        Telescope::recordDump(
            IncomingDumpEntry::make(['dump' => $dump])
        );
    }
}
