<?php

namespace Laravel\Telescope;

use Exception;
use Laravel\Telescope\Contracts\TelescopeException as TelescopeExceptionInterface;

class TelescopeException extends Exception implements TelescopeExceptionInterface
{
    protected $context = [];

    public function getContext(): array
    {
        return $this->context;
    }
}
