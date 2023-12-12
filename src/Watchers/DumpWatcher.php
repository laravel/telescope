<?php

namespace Laravel\Telescope\Watchers;

use Exception;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Laravel\Telescope\IncomingDumpEntry;
use Laravel\Telescope\Telescope;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

class DumpWatcher extends Watcher
{
    /**
     * The cache factory implementation.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * Create a new watcher instance.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  array  $options
     * @return void
     */
    public function __construct(CacheFactory $cache, array $options = [])
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
    public function register($app)
    {
        $dumpWatcherCache = false;

        try {
            $dumpWatcherCache = $this->cache->get('telescope:dump-watcher');
        } catch (Exception) {
            //
        }

        if (! ($this->options['always'] ?? false) && ! $dumpWatcherCache) {
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
