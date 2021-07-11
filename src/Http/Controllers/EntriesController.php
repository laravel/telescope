<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\ClearableRepository;

class EntriesController extends Controller
{
    /**
     * Delete all of the entries from storage.
     *
     * @param  \Laravel\Telescope\Contracts\ClearableRepository  $storage
     * @return void
     */
    public function destroy(ClearableRepository $storage)
    {
        $storage->clear();
    }
}
