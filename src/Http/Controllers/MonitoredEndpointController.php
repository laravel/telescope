<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;

class MonitoredEndpointController extends Controller
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
     * Get all of the endpoints being monitored.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            'endpoints' => $this->entries->monitoringEndpoints(),
        ]);
    }

    /**
     * Begin monitoring the given endpoint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request)
    {
        $this->entries->monitorEndpoints([$request->endpoint]);
    }

    /**
     * Stop monitoring the given endpoint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function destroy(Request $request)
    {
        $this->entries->stopMonitoringEndpoints([$request->endpoint]);
    }
}
