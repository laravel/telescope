<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\ModelWatcher;

class ModelWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            ModelWatcher::class => [
                'enabled' => true,
                'events' => ['eloquent.created*', 'eloquent.updated*', 'eloquent.retrieved*'],
                'hydrations' => true,
            ],
        ]);
    }

    public function test_model_watcher_registers_entry()
    {
        Telescope::withoutRecording(function () {
            $this->loadLaravelMigrations();
        });

        UserEloquent::query()
            ->create([
                'name' => 'Telescope',
                'email' => 'telescope@laravel.com',
                'password' => 1,
            ]);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::MODEL, $entry->type);
        $this->assertSame('created', $entry->content['action']);
        $this->assertSame(UserEloquent::class.':1', $entry->content['model']);
    }

    public function test_model_watcher_can_restrict_events()
    {
        Telescope::withoutRecording(function () {
            $this->loadLaravelMigrations();
        });

        $user = UserEloquent::query()
            ->create([
                'name' => 'Telescope',
                'email' => 'telescope@laravel.com',
                'password' => 1,
            ]);

        $user->delete();

        $entries = $this->loadTelescopeEntries();
        $entry = $entries->last();

        $this->assertCount(1, $entries);
        $this->assertSame(EntryType::MODEL, $entry->type);
        $this->assertSame('created', $entry->content['action']);
        $this->assertSame(UserEloquent::class.':1', $entry->content['model']);
    }

    public function test_model_watcher_registers_hydration_entry()
    {
        Telescope::withoutRecording(function () {
            $this->loadLaravelMigrations();
        });

        Telescope::stopRecording();
        $this->createUser();
        $this->createUser();
        $this->createUser();

        Telescope::startRecording();
        UserEloquent::all();
        Telescope::stopRecording();

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::MODEL, $entry->type);
        $this->assertSame(3, $entry->content['count']);
        $this->assertSame(UserEloquent::class, $entry->content['model']);
        $this->assertCount(1, $this->loadTelescopeEntries());
    }

    protected function createUser()
    {
        UserEloquent::create([
            'name' => 'Telescope',
            'email' => Str::random(),
            'password' => 1,
        ]);
    }
}

class UserEloquent extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}
