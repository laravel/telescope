<?php

namespace Laravel\Telescope\Tests\Storage;

use Illuminate\Support\Facades\Bus;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Jobs\ProcessPendingUpdates;
use Laravel\Telescope\Tests\FeatureTestCase;
use Mockery as m;

class ProcessPendingUpdatesTest extends FeatureTestCase
{
    public function test_pending_updates()
    {
        Bus::fake();

        $pendingUpdates = collect([
            ['id' => 1, 'content' => 'foo'],
            ['id' => 2, 'content' => 'bar'],
        ]);

        $failedUpdates = collect();
        $repository = m::mock(EntriesRepository::class);

        $repository
            ->shouldReceive('update')
            ->once()
            ->with($pendingUpdates)
            ->andReturn($failedUpdates);

        (new ProcessPendingUpdates($pendingUpdates))->handle($repository);

        Bus::assertNothingDispatched();
    }

    public function test_pending_updates_may_stay_pending()
    {
        Bus::fake();

        $pendingUpdates = collect([
            ['id' => 1, 'content' => 'foo'],
            ['id' => 2, 'content' => 'bar'],
        ]);
        $failedUpdates = collect([
            $pendingUpdates->get(1),
        ]);

        $repository = m::mock(EntriesRepository::class);

        $repository
            ->shouldReceive('update')
            ->once()
            ->with($pendingUpdates)
            ->andReturn($failedUpdates);

        (new ProcessPendingUpdates($pendingUpdates))->handle($repository);

        Bus::assertDispatched(ProcessPendingUpdates::class, function ($job) {
            return $job->attempt == 1 && $job->pendingUpdates->toArray() == [['id' => 2, 'content' => 'bar']];
        });
    }

    public function test_pending_updates_may_stay_pending_only_three_times()
    {
        Bus::fake();

        $pendingUpdates = collect([
            ['id' => 1, 'content' => 'foo'],
            ['id' => 2, 'content' => 'bar'],
        ]);
        $failedUpdates = collect([
            $pendingUpdates->get(1),
        ]);

        $repository = m::mock(EntriesRepository::class);

        $repository
            ->shouldReceive('update')
            ->once()
            ->with($pendingUpdates)
            ->andReturn($failedUpdates);

        (new ProcessPendingUpdates($pendingUpdates, 2))->handle($repository);

        Bus::assertNothingDispatched();
    }
}
