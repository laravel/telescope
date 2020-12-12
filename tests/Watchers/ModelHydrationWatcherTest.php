<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\ModelHydrationWatcher;

class ModelHydrationWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            ModelHydrationWatcher::class => true,
        ]);
    }

    public function test_model_hydration_watcher_registers_entry()
    {
        Telescope::withoutRecording(function () {
            $this->loadLaravelMigrations();
        });

        $this->createUser();
        $this->createUser();
        $this->createUser();

        TestUser::all();
        Telescope::stopRecording();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::HYDRATION, $entry->type);
        $this->assertSame(3, $entry->content['count']);
        $this->assertSame(TestUser::class, $entry->content['model']);
        $this->assertCount(1, $this->loadTelescopeEntries());
    }

    protected function createUser()
    {
        TestUser::create([
            'name' => 'Telescope',
            'email' => Str::random(),
            'password' => 1,
        ]);
    }
}

class TestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}

