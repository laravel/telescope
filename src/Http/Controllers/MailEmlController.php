<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Telescope\EntryType;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;

class MailEmlController extends Controller
{
    /**
     * Download the Eml content of the email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  int  $id
     * @return mixed
     */
    public function show(Request $request, EntriesRepository $storage, $id)
    {
        return response($storage->find($id)->content->raw, 200, [
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="mail-'.$id.'.eml"',
        ]);
    }
}
