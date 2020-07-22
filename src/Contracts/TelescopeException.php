<?php

namespace Laravel\Telescope\Contracts;

use Throwable;

interface TelescopeException extends Throwable
{
    public function getContext(): array;
}
