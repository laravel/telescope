<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Telescope\EntryType;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;

class MailController extends EntryController
{
    /**
     * Preview the HTML content of the email.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository $storage
     * @param  integer $id
     * @return mixed
     */
    public function previewHtml(Request $request, EntriesRepository $storage, $id)
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
    public function downloadEml(Request $request, EntriesRepository $storage, $id)
    {
        $mail = $storage->find($id);

        return response($mail->content->raw, 200, [
            'Content-Type' => 'message/rfc822',
            'Content-Disposition' => 'attachment; filename="mail-'.$id.'.eml"',
        ]);
    }

    /**
     * The entry type for the controller.
     *
     * @return int
     */
    protected function entryType()
    {
        return EntryType::MAIL;
    }
}
