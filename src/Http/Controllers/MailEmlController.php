<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Laravel\Telescope\Contracts\EntriesRepository;

class MailEmlController extends Controller
{
    /**
     * Download the Eml content of the email.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $responseFactory
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(EntriesRepository $storage, ResponseFactory $responseFactory, $id)
    {
        return $responseFactory->make($storage->find($id)->content['raw'], 200, [
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="mail-'.$id.'.eml"',
        ]);
    }
}
