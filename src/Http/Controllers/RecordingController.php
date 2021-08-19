<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\Telescope;

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
     * @return void
     */
    public function __construct()
    {
        $this->cache = Telescope::cacheStore();
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
