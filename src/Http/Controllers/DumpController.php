<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Contracts\EntriesRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class DumpController extends EntryController
{
    /**
     * The cache factory implementation.
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @return void
     */
    public function __construct(CacheFactory $cache)
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
        $this->cache->put('telescope:dump-watcher', true, now()->addSecond(4));

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
}
