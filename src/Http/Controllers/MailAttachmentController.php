<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;
use Symfony\Component\HttpFoundation\HeaderUtils;

class MailAttachmentController extends Controller
{
    /**
     * Download a mail attachment.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  int  $id
     * @param  int  $index
     * @return mixed
     */
    public function show(EntriesRepository $storage, $id, $index)
    {
        $attachments = $storage->find($id)->content['attachments'] ?? [];

        if (! isset($attachments[$index])) {
            abort(404);
        }

        $attachment = $attachments[$index];

        $content = base64_decode($attachment['content']);

        return response($content, 200, [
            'Content-Type' => $attachment['mime_type'],
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $attachment['filename']),
            'Content-Length' => strlen($content),
        ]);
    }
}
