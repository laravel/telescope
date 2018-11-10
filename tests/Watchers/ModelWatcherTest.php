<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\ModelWatcher;
use Laravel\Telescope\Tests\Fixtures\UserEloquent;

class ModelWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            ModelWatcher::class => true,
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
}
