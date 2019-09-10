<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Routing\Controller;

class RecordingController extends Controller
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
     * Toggle recording.
     *
     * @return void
     */
    public function toggle()
    {
        if ($this->cache->get('telescope:pause-recording')) {
            $this->cache->forget('telescope:pause-recording');
        } else {
            $this->cache->put('telescope:pause-recording', true, now()->addDays(30));
        }
    }
}
