<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

class HomeController extends Controller
{
    /**
     * Display the Telescope view.
     *
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $responseFactory
     * @return \Illuminate\Http\Response
     */
    public function index(ResponseFactory $responseFactory)
    {
        return $responseFactory->view('telescope::layout');
    }
}
