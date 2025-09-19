<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Support\Facades\DB;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\NPlusOneWatcher;

class NPlusOneWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            NPlusOneWatcher::class => true,
        ]);
    }

    public function testDetectsNPlusOneAndRecordsLog()
    {
        $this->app->setBasePath(dirname(__FILE__, 3));

        // Repeat the same query on the same code line.
        for ($i = 0; $i < 5; $i++) {
            DB::select('select 1');
        }

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertNotNull($entry);
        $this->assertSame(EntryType::LOG, $entry->type);
        $this->assertStringStartsWith('N+1 detected at ', $entry->content['message']);
    }
}
