<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;

class RouterController extends Controller
{
    /**
     * Display the Telescope Vue router.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        return view('telescope::layout');
    }
}
