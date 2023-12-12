<?php

namespace Laravel\Telescope\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class PauseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:pause';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pause all Telescope watchers';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function handle(CacheRepository $cache)
    {
        if (! $cache->get('telescope:pause-recording')) {
            $cache->put('telescope:pause-recording', true, now()->addDays(30));
        }

        $this->info('Telescope watchers paused successfully.');
    }
}
