<?php

namespace Laravel\Telescope;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

class ExtractProperties
{
    /**
     * Extract the properties for the given object in array form.
     *
     * The given array is ready for storage.
     *
     * @param  mixed  $target
     * @return array
     */
    public static function from($target)
    {
        return collect((new ReflectionClass($target))->getProperties())
            ->mapWithKeys(function ($property) use ($target) {
                $property->setAccessible(true);

                if (($value = $property->getValue($target)) instanceof Model) {
                    return [$property->getName() => FormatModel::given($value)];
                } elseif (is_object($value)) {
                    return [
                        $property->getName() => [
                            'class' => get_class($value),
                            'properties' => json_decode(json_encode($value), true),
                        ],
                    ];
                } else {
                    return [$property->getName() => json_decode(json_encode($value), true)];
                }
            })->toArray();
    }
}
