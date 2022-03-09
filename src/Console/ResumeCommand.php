<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ResumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:resume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resume all telescope watchers';

    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected CacheRepository $cache;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->cache->get('telescope:pause-recording')) {
            $this->cache->forget('telescope:pause-recording');
        }

        $this->info('Telescope was resumed successfully.');
    }
}
