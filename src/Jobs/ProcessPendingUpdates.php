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
     * The number of times the job may be attempted.
     *
     * @var int
     */
    protected $attempt;

    /**
     * The pending updates list.
     *
     * @var \Illuminate\Support\Collection<int, \Laravel\Telescope\EntryUpdate>
     */
    protected $updates;

    /**
     * Creates a new process pending entry updates instance.
     *
     * @param  \Illuminate\Support\Collection  $updates
     * @param  int  $attempt
     * @return void
     */
    public function __construct($updates, $attempt = 0)
    {
        $this->updates = $updates;
        $this->attempt = $attempt;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EntriesRepository $repository)
    {
        $this->attempt++;

        $repository->update($this->updates)->whenNotEmpty(
            fn ($pendingUpdates) => static::dispatchIf(
                $this->attempt < 3, $pendingUpdates, $this->attempt
            )->delay(now()->addSeconds(10)),
        );
    }
}
