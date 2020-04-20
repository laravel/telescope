<?php

namespace Laravel\Telescope;

use Illuminate\Support\Arr;

class FormatModel
{
    /**
     * Format the given model to a readable string.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return string
     */
    public static function given($model)
    {
        return get_class($model).':'.implode('_', Arr::wrap($model->getKey()));
    }
}
