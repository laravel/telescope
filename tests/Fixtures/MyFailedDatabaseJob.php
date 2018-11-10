<?php

namespace Laravel\Telescope\Tests\Fixtures;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class MyFailedDatabaseJob implements ShouldQueue
{
    public $connection = 'database';

    public $tries = 1;

    private $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        throw new Exception($this->message);
    }
}
