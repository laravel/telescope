<?php

namespace Laravel\Telescope;

use BackedEnum;
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

        if ($keys instanceof BackedEnum) {
            $keys = $keys->value;
        }

        return get_class($model).':'.implode('_', Arr::wrap($keys));
    }
}
