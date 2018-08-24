<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;

class CacheController extends Controller
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
            'entries' => $storage->all(6)
        ]);
    }

    /**
     * Get an entry with the given ID.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository $storage
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, EntriesRepository $storage, $id)
    {
        return response()->json([
            'entry' => $storage->find($id)
        ]);
    }
}