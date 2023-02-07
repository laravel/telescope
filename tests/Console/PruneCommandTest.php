<?php

namespace Laravel\Telescope\Tests\Console;

use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\Storage\EntryTable;
use Laravel\Telescope\Tests\FeatureTestCase;

class PruneCommandTest extends FeatureTestCase
{
    use EntryTable;

    public function test_prune_command_will_clear_old_records()
    {
        $recent = EntryModelFactory::new()->create(['created_at' => now()]);

        $old = EntryModelFactory::new()->create(['created_at' => now()->subDays(2)]);

        $this->artisan('telescope:prune')->expectsOutput('1 entries pruned.');

        $this->assertDatabaseHas($this->getEntriesTableName(), ['uuid' => $recent->uuid]);

        $this->assertDatabaseMissing($this->getEntriesTableName(), ['uuid' => $old->uuid]);
    }

    public function test_prune_command_can_vary_hours()
    {
        $recent = EntryModelFactory::new()->create(['created_at' => now()->subHours(5)]);

        $this->artisan('telescope:prune')->expectsOutput('0 entries pruned.');

        $this->artisan('telescope:prune', ['--hours' => 4])->expectsOutput('1 entries pruned.');

        $this->assertDatabaseMissing($this->getEntriesTableName(), ['uuid' => $recent->uuid]);
    }
}
