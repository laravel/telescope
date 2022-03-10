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
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(CacheRepository $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->cache->get('telescope:pause-recording')) {
            $this->cache->put('telescope:pause-recording', true, now()->addDays(30));
        }

        $this->info('Telescope watchers paused successfully.');
    }
}
