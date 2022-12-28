<?php

namespace Laravel\Telescope;

use Illuminate\Database\Eloquent\Relations\Pivot;
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
        if ($model instanceof Pivot && ! $model->incrementing) {
            $keys = [
                $model->getAttribute($model->getForeignKey()),
                $model->getAttribute($model->getRelatedKey()),
            ];
        } else {
            $keys = $model->getKey();
        }

        $keys = Arr::wrap($keys);

        if (interface_exists('BackedEnum')) {
            $keys = array_map(function ($value) {
                return ($value instanceof \BackedEnum) ? $value->value : $value;
            }, $keys);
        }

        return get_class($model).':'.implode('_', $keys);
    }
}
