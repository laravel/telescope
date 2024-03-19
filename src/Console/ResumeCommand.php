<?php

namespace Laravel\Telescope\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'telescope:resume')]
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
    protected $description = 'Unpause all Telescope watchers';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function handle(CacheRepository $cache)
    {
        if ($cache->get('telescope:pause-recording')) {
            $cache->forget('telescope:pause-recording');
        }

        $this->info('Telescope watchers resumed successfully.');
    }
}
