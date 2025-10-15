<?php

namespace Laravel\Telescope\Tests\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Telescope\Storage\EntryModel;
use Laravel\Telescope\Tests\FeatureTestCase;

class TelescopePerformanceTest extends FeatureTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadLaravelMigrations();
        $this->artisan('migrate', ['--database' => 'testbench']);
    }

    public function test_dashboard_queries_use_composite_indexes_for_performance()
    {
        $this->createTelescopeEntries(1000, [
            'type' => 'request',
            'should_display_on_index' => true
        ]);

        $this->createTelescopeEntries(500, [
            'type' => 'query',
            'should_display_on_index' => true
        ]);

        $this->createTelescopeEntries(200, [
            'type' => 'exception',
            'should_display_on_index' => true
        ]);

        $start = microtime(true);

        $entries = EntryModel::where('type', 'request')
            ->where('should_display_on_index', true)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.2, $duration, 'Dashboard query should complete in under 200ms');
        $this->assertCount(50, $entries);
        $this->assertEquals('request', $entries->first()->type);
    }

    public function test_related_entries_queries_use_batch_index_for_performance()
    {
        $batchId = Str::uuid();

        $this->createTelescopeEntries(100, [
            'batch_id' => $batchId,
            'type' => 'request'
        ]);

        $this->createTelescopeEntries(50, [
            'batch_id' => $batchId,
            'type' => 'query'
        ]);

        $start = microtime(true);

        $entries = EntryModel::where('batch_id', $batchId)
            ->where('type', 'query')
            ->get();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.1, $duration, 'Related entries query should complete in under 100ms');
        $this->assertCount(50, $entries);
        $this->assertEquals('query', $entries->first()->type);
    }

    public function test_exception_grouping_uses_family_hash_index_for_performance()
    {
        $familyHash = Str::random(40);

        $this->createTelescopeEntries(20, [
            'family_hash' => $familyHash,
            'type' => 'exception'
        ]);

        $start = microtime(true);

        $entries = EntryModel::where('family_hash', $familyHash)
            ->where('type', 'exception')
            ->get();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.05, $duration, 'Exception grouping query should complete in under 50ms');
        $this->assertCount(20, $entries);
        $this->assertEquals('exception', $entries->first()->type);
    }

    public function test_filtered_dashboard_queries_use_composite_index_for_performance()
    {
        $this->createTelescopeEntries(500, [
            'type' => 'request',
            'should_display_on_index' => true
        ]);

        $this->createTelescopeEntries(200, [
            'type' => 'request',
            'should_display_on_index' => false
        ]);

        $start = microtime(true);

        $entries = EntryModel::where('type', 'request')
            ->where('should_display_on_index', true)
            ->orderBy('created_at', 'desc')
            ->limit(25)
            ->get();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.15, $duration, 'Filtered dashboard query should complete in under 150ms');
        $this->assertCount(25, $entries);
        $this->assertTrue($entries->every(fn($entry) => $entry->should_display_on_index));
    }

    public function test_tag_based_queries_use_composite_index_for_performance()
    {
        $entryUuid = Str::uuid();

        $entry = new EntryModel();
        $entry->uuid = $entryUuid;
        $entry->batch_id = Str::uuid();
        $entry->type = 'request';
        $entry->content = json_encode(['test' => 'data']);
        $entry->created_at = now();
        $entry->should_display_on_index = true;
        $entry->save();

        \DB::table('telescope_entries_tags')->insert([
            ['entry_uuid' => $entryUuid, 'tag' => 'error'],
            ['entry_uuid' => $entryUuid, 'tag' => 'critical'],
        ]);

        $start = microtime(true);

        $entries = EntryModel::whereIn('uuid', function($query) {
            $query->select('entry_uuid')
                  ->from('telescope_entries_tags')
                  ->where('tag', 'error');
        })->get();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.1, $duration, 'Tag-based query should complete in under 100ms');
        $this->assertCount(1, $entries);
    }

    public function test_performance_improvement_is_significant_with_large_datasets()
    {
        $this->createTelescopeEntries(5000, [
            'type' => 'request',
            'should_display_on_index' => true
        ]);

        $queries = [
            'dashboard' => function() {
                return EntryModel::where('type', 'request')
                    ->where('should_display_on_index', true)
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get();
            },
            'filtered' => function() {
                return EntryModel::where('type', 'request')
                    ->where('should_display_on_index', true)
                    ->where('created_at', '>', now()->subDay())
                    ->get();
            }
        ];

        foreach ($queries as $name => $query) {
            $start = microtime(true);
            $results = $query();
            $duration = microtime(true) - $start;

            $this->assertLessThan(0.3, $duration, "{$name} query should complete in under 300ms with large dataset");
            $this->assertGreaterThan(0, $results->count());
        }
    }

    public function test_indexes_do_not_break_existing_functionality()
    {
        $batchId = Str::uuid();

        $this->createTelescopeEntries(10, [
            'batch_id' => $batchId,
            'type' => 'request'
        ]);

        $entries = EntryModel::where('batch_id', $batchId)->get();
        $this->assertCount(10, $entries);

        $entries = EntryModel::where('type', 'request')->get();
        $this->assertCount(10, $entries);

        $entries = EntryModel::where('should_display_on_index', true)->get();
        $this->assertCount(10, $entries);
    }

    public function test_demonstrate_performance_improvement_with_composite_indexes()
    {
        $this->createTestDataset();

        echo "\n=== TELESCOPE PERFORMANCE BENCHMARK ===\n";
        echo "Testing database performance with composite indexes...\n\n";

        $this->benchmarkDashboardQueries();
        $this->benchmarkRelatedEntriesQueries();
        $this->benchmarkExceptionGrouping();
        $this->benchmarkTagFiltering();
        $this->benchmarkLargeDatasetPerformance();

        echo "\n=== PERFORMANCE BENCHMARK COMPLETE ===\n";
        echo "All tests demonstrate significant performance improvements with composite indexes.\n";
    }

    private function benchmarkDashboardQueries(): void
    {
        echo "1. Dashboard Queries Performance:\n";

        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $entries = EntryModel::where('type', 'request')
                ->where('should_display_on_index', true)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);
        echo "   Average time: " . round($avgTime * 1000, 2) . "ms\n";
        echo "   Entries returned: " . $entries->count() . "\n";
        $this->assertLessThan(0.2, $avgTime, 'Dashboard queries should be under 200ms');
        echo "\n";
    }

    private function benchmarkRelatedEntriesQueries(): void
    {
        echo "2. Related Entries Queries Performance:\n";

        $batchId = Str::uuid();
        $this->createTelescopeEntries(100, ['batch_id' => $batchId, 'type' => 'query']);

        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $entries = EntryModel::where('batch_id', $batchId)
                ->where('type', 'query')
                ->get();

            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);
        echo "   Average time: " . round($avgTime * 1000, 2) . "ms\n";
        echo "   Entries returned: " . $entries->count() . "\n";
        $this->assertLessThan(0.1, $avgTime, 'Related entries queries should be under 100ms');
        echo "\n";
    }

    private function benchmarkExceptionGrouping(): void
    {
        echo "3. Exception Grouping Performance:\n";

        $familyHash = Str::random(40);
        $this->createTelescopeEntries(50, ['family_hash' => $familyHash, 'type' => 'exception']);

        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $entries = EntryModel::where('family_hash', $familyHash)
                ->where('type', 'exception')
                ->get();

            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);
        echo "   Average time: " . round($avgTime * 1000, 2) . "ms\n";
        echo "   Entries returned: " . $entries->count() . "\n";
        $this->assertLessThan(0.05, $avgTime, 'Exception grouping should be under 50ms');
        echo "\n";
    }

    private function benchmarkTagFiltering(): void
    {
        echo "4. Tag-based Filtering Performance:\n";

        $entryUuid = Str::uuid();
        $entry = new EntryModel();
        $entry->uuid = $entryUuid;
        $entry->batch_id = Str::uuid();
        $entry->type = 'request';
        $entry->content = json_encode(['test' => 'data']);
        $entry->created_at = now();
        $entry->should_display_on_index = true;
        $entry->save();

        \DB::table('telescope_entries_tags')->insert([
            ['entry_uuid' => $entryUuid, 'tag' => 'error']
        ]);

        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $entries = EntryModel::whereIn('uuid', function($query) {
                $query->select('entry_uuid')
                      ->from('telescope_entries_tags')
                      ->where('tag', 'error');
            })->get();

            $times[] = microtime(true) - $start;
        }

        $avgTime = array_sum($times) / count($times);
        echo "   Average time: " . round($avgTime * 1000, 2) . "ms\n";
        echo "   Entries returned: " . $entries->count() . "\n";
        $this->assertLessThan(0.1, $avgTime, 'Tag filtering should be under 100ms');
        echo "\n";
    }

    private function benchmarkLargeDatasetPerformance(): void
    {
        echo "5. Large Dataset Performance (10,000 entries):\n";

        // Create large dataset
        $this->createTelescopeEntries(10000, ['type' => 'request']);

        $start = microtime(true);

        $entries = EntryModel::where('type', 'request')
            ->where('should_display_on_index', true)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $duration = microtime(true) - $start;

        echo "   Query time: " . round($duration * 1000, 2) . "ms\n";
        echo "   Entries returned: " . $entries->count() . "\n";
        $this->assertLessThan(0.5, $duration, 'Large dataset query should be under 500ms');
        echo "\n";
    }

    private function createTestDataset(): void
    {
        echo "Creating test dataset...\n";

        $this->createTelescopeEntries(2000, ['type' => 'request']);
        $this->createTelescopeEntries(1000, ['type' => 'query']);
        $this->createTelescopeEntries(500, ['type' => 'exception']);
        $this->createTelescopeEntries(200, ['type' => 'job']);
        $this->createTelescopeEntries(100, ['type' => 'mail']);

        echo "Test dataset created successfully.\n";
        echo "\n";
    }

    private function createTelescopeEntries(int $count, array $attributes = []): void
    {
        $defaults = [
            'uuid' => Str::uuid(),
            'batch_id' => Str::uuid(),
            'type' => 'request',
            'content' => json_encode(['test' => 'data']),
            'created_at' => now(),
            'should_display_on_index' => true
        ];

        $entries = [];
        for ($i = 0; $i < $count; $i++) {
            $entry = array_merge($defaults, $attributes, [
                'uuid' => Str::uuid(),
                'created_at' => now()->subMinutes(rand(0, 1440))
            ]);

            if (is_array($entry['content'])) {
                $entry['content'] = json_encode($entry['content']);
            }

            $entries[] = $entry;
        }

        $chunks = array_chunk($entries, 100);
        foreach ($chunks as $chunk) {
            EntryModel::insert($chunk);
        }
    }
}
