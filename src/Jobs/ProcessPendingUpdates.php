<?php

namespace Laravel\Telescope\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Telescope\Contracts\EntriesRepository;

class ProcessPendingUpdates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The pending entry updates.
     *
     * @var \Illuminate\Support\Collection<int, \Laravel\Telescope\EntryUpdate>
     */
    public $pendingUpdates;

    /**
     * The number of times the job has been attempted.
     *
     * @var int
     */
    public $attempt;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Support\Collection<int, \Laravel\Telescope\EntryUpdate>
     * @param  int  $attempt
     * @return void
     */
    public function __construct($pendingUpdates, $attempt = 0)
    {
        $this->pendingUpdates = $pendingUpdates;
        $this->attempt = $attempt;
    }

    /**
     * Execute the job.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $repository
     * @return void
     */
    public function handle(EntriesRepository $repository)
    {
        $this->attempt++;

        $repository->update($this->pendingUpdates)->whenNotEmpty(
            fn ($pendingUpdates) => static::dispatchIf(
                $this->attempt < 3, $pendingUpdates, $this->attempt
            )->delay(now()->addSeconds(10)),
        );
    }
}
