<?php

namespace Laravel\Telescope;

use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;

trait ExtractsMailableTags
{
    /**
     * Register a callback to extract mailable tags.
     *
     * @return void
     */
    protected static function registerMailableTagExtractor()
    {
        Mailable::buildViewDataUsing(function ($mailable) {
            return [
                '__telescope' => ExtractTags::from($mailable),
                '__telescope_mailable' => get_class($mailable),
                '__telescope_queued' => in_array(ShouldQueue::class, class_implements($mailable)),
            ];
        });
    }
}
