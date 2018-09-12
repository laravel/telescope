<?php

namespace Laravel\Telescope;

use Throwable;
use Whoops\Exception\Inspector;

class ExceptionContext
{
    /**
     * Get the exception code context for the given exception.
     *
     * @param  \Throwable  $exception
     * @return array
     */
    public static function get(Throwable $exception)
    {
        $result = (new Inspector($exception))
                ->getFrames()[0]
                ->getFileLines($exception->getLine() - 10, 20);

        return collect($result)->mapWithKeys(function ($value, $key) {
            return [$key + 1 => $value];
        })->all();
    }
}
