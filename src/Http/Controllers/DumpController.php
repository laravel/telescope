<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Cache\ArrayStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Watchers\DumpWatcher;
use Laravel\Telescope\Contracts\EntriesRepository;
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
        if ($this->cache->getStore() instanceof  ArrayStore) {
            abort(400, 'The Array cache driver cannot be used for Dumps. Please use a persistent cache.');
        }
        $this->cache->put('telescope:dump-watcher', true, now()->addSeconds(4));

        return parent::index($request, $storage);
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
