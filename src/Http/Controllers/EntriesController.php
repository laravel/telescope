<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\ClearableRepository;

class EntriesController extends Controller
{    
    /**
     * Clear entries.
     *
     * @return void
     */
    public function clear(ClearableRepository $storage)
    {
        $storage->clear();
    }
}
