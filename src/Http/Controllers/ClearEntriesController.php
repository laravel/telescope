<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class ClearEntriesController extends Controller
{
    /**
     * Clear entries.
     *
     * @return void
     */
    public function clear()
    {
        Artisan::call('telescope:clear');
    }
}
