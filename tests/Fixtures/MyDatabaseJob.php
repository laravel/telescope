<?php

namespace Laravel\Telescope\Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

class MyDatabaseJob implements ShouldQueue
{
    public $connection = 'database';

    public $queue = 'on-demand';

    private $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        //
    }
}
