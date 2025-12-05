<?php

namespace Laravel\Telescope;

use Illuminate\Support\Facades\Context;

class ContextHelper
{
    /**
     * Get the current context data.
     *
     * @return array|null
     */
    public static function get()
    {
        if (! class_exists(Context::class)) {
            return null;
        }

        $context = Context::all();

        return ! empty($context) ? $context : null;
    }
}