<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;

class MailHtmlController extends Controller
{
    /**
     * Get the HTML content of the given email.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(EntriesRepository $storage, $id)
    {
        return response($storage->find($id)->content['html'], 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => 'sandbox allow-popups allow-popups-to-escape-sandbox',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
