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
        $keyName = $model->getKeyName();

        if ($model instanceof Pivot && ! $model->incrementing) {
            $keys = [
                $model->getAttribute($model->getForeignKey()),
                $model->getAttribute($model->getRelatedKey()),
            ];
        } elseif (is_array($keyName)) {
            $keys = array_map(function ($key) use ($model) {
                return $model->getAttribute($key);
            }, $keyName);
        } else {
            $keys = $model->getKey();
        }

        return get_class($model).':'.implode('_', array_map(function ($value) {
            return $value instanceof BackedEnum ? $value->value : $value;
        }, Arr::wrap($keys)));
    }
}
