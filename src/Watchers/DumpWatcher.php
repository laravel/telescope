<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingDumpEntry;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Laravel\Telescope\Contracts\EntriesRepository;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class DumpWatcher extends Watcher
{
    /**
     * The cache factory implementation.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * @var EntriesRepository
     */
    protected $entriesRepository;

    /**
     * Create a new watcher instance.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  array  $options
     * @return void
     */
    public function __construct(
        CacheFactory $cache,
        EntriesRepository $entriesRepository,
        array $options = []
    ) {
        parent::__construct($options);

        $this->cache = $cache;
        $this->entriesRepository = $entriesRepository;
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

        Telescope::store($this->entriesRepository, false);
    }
}
