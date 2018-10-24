<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Laravel\Telescope\Contracts\EntriesRepository;

class MonitoredTagController extends Controller
{
    /**
     * The entry repository implementation.
     *
     * @var \Laravel\Telescope\Contracts\EntriesRepository
     */
    protected $entries;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $entries
     * @return void
     */
    public function __construct(EntriesRepository $entries)
    {
        $this->entries = $entries;
    }

    /**
     * Get all of the tags being monitored.
     *
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $responseFactory
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ResponseFactory $responseFactory)
    {
        return $responseFactory->json([
            'tags' => $this->entries->monitoring()
        ]);
    }

    /**
     * Begin monitoring the given tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request)
    {
        $this->entries->monitor([$request->tag]);
    }

    /**
     * Stop monitoring the given tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function destroy(Request $request)
    {
        $this->entries->stopMonitoring([$request->tag]);
    }
}
