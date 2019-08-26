<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Cache\ArrayStore;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Watchers\DumpWatcher;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Laravel\Telescope\Contracts\EntriesRepository;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class DumpController extends EntryController
{
    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * List the entries of the given type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, EntriesRepository $storage)
    {
        $this->cache->put('telescope:dump-watcher', true, now()->addSeconds(4));

        return response()->json([
            'dump' => (new HtmlDumper())->dump((new VarCloner)->cloneVar(true), true),
            'entries' => $storage->get(
                $this->entryType(),
                EntryQueryOptions::fromRequest($request)
            ),
            'status' => $this->status(),
        ]);
    }

    /**
     * Determine the watcher recording status.
     *
     * @return string
     */
    protected function status()
    {
        if ($this->cache->getStore() instanceof ArrayStore) {
            return 'wrong-cache';
        }

        return parent::status();
    }

    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType()
    {
        return EntryType::DUMP;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher()
    {
        return DumpWatcher::class;
    }
}
