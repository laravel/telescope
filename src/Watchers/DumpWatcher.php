<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingDumpEntry;
use Illuminate\Contracts\Cache\Repository;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class DumpWatcher extends Watcher
{
    /**
     * The cache factory implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new watcher instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  array  $options
     * @return void
     */
    public function __construct(Repository $cache, array $options = [])
    {
        parent::__construct($options);

        $this->cache = $cache;
    }

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register(Application $app)
    {
        if (! $this->cache->get('telescope:dump-watcher')) {
            return;
        }

        VarDumper::setHandler(function ($var) {
            $this->recordDump((new HtmlDumper)->dump(
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
