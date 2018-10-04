<?php

namespace Laravel\Telescope;

use Illuminate\Mail\Mailable;

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
            ];
        });
    }
}
