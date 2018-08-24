<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;

class MailController extends Controller
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
            'entries' => $storage->all(1)
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

    /**
     * Preview the HTML content of the email.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository $storage
     * @param  integer $id
     * @return mixed
     */
    public function previewHTML(Request $request, EntriesRepository $storage, $id)
    {
        $mail = $storage->find($id);

        return $mail->content->html;
    }

    /**
     * Preview the HTML content of the email.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository $storage
     * @param  integer $id
     * @return mixed
     */
    public function downloadEML(Request $request, EntriesRepository $storage, $id)
    {
        $mail = $storage->find($id);

        return response($mail->content->raw, 200, [
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="mail-'.$id.'.eml"',
        ]);
    }
}