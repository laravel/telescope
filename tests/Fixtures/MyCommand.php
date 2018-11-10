<?php

namespace Laravel\Telescope\Tests\Fixtures;

use Illuminate\Console\Command;

class MyCommand extends Command
{
    protected $signature = 'telescope:test-command';

    public function handle()
    {
        //
    }
}
