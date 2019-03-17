<?php

namespace Laravel\Telescope;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class FormatModel
{
    /**
     * Format the given model to a readable string.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    public static function given($model)
    {
        if (method_exists($model, 'getTelescopeKey')) {
            $key = $model->getTelescopeKey();
        } else {
            $key = $model->getKey();
        }

        return get_class($model).':'.implode('_', Arr::wrap($key));
    }
}
