<?php

namespace Laravel\Telescope\Tests\Fixtures\Ai;

use Laravel\Ai\Exceptions\FailoverableException;
use RuntimeException;

class FailoverException extends RuntimeException implements FailoverableException
{
    //
}
