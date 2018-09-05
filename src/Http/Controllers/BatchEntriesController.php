<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;

class BatchEntriesController extends Controller
{
    /**
     * List the entries of the given type.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository $storage
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, EntriesRepository $storage)
    {
        return response()->json([
            'entries' => $storage->get(null, $request->all())
        ]);
    }
}
