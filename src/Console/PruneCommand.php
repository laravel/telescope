<?php

namespace Laravel\Telescope\Console;

use Illuminate\Console\Command;
use Laravel\Telescope\Contracts\PrunableRepository;

class PruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:prune {--hours=24 : The number of hours to retain Telescope data} {--q|quiet : Suppress output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale entries from the Telescope database';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Telescope\Contracts\PrunableRepository  $repository
     * @return void
     */
    public function handle(PrunableRepository $repository)
    {
        $prunedCount = $repository->prune(now()->subHours($this->option('hours')));
        if (! $this->option('quiet')) {
            $this->info($prunedCount.' entries pruned.');
        }
    }
}
