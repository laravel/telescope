<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Watchers\GateWatcher;
use Laravel\Telescope\Tests\FeatureTestCase;
use Illuminate\Contracts\Auth\Authenticatable;

class GateWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            GateWatcher::class => true,
        ]);

        Gate::define('potato', function (User $user) {
            return $user->email === 'allow';
        });

        Gate::define('guest potato', function (?User $user) {
            return true;
        });

        Gate::define('deny potato', function (?User $user) {
            return false;
        });
    }

    public function test_gate_watcher_registers_allowed_entries()
    {
        $check = Gate::forUser(new User('allow'))->check('potato');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertTrue($check);
        $this->assertSame(EntryType::GATE, $entry->type);
        $this->assertSame('potato', $entry->content['ability']);
        $this->assertSame('allowed', $entry->content['result']);
        $this->assertEmpty($entry->content['arguments']);
    }

    public function test_gate_watcher_registers_denied_entries()
    {
        $check = Gate::forUser(new User('deny'))->check('potato', ['banana']);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertFalse($check);
        $this->assertSame(EntryType::GATE, $entry->type);
        $this->assertSame('potato', $entry->content['ability']);
        $this->assertSame('denied', $entry->content['result']);
        $this->assertSame(['banana'], $entry->content['arguments']);
    }

    public function test_gate_watcher_registers_allowed_guest_entries()
    {
        $check = Gate::check('guest potato');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertTrue($check);
        $this->assertSame(EntryType::GATE, $entry->type);
        $this->assertSame('guest potato', $entry->content['ability']);
        $this->assertSame('allowed', $entry->content['result']);
        $this->assertEmpty($entry->content['arguments']);
    }

    public function test_gate_watcher_registers_denied_guest_entries()
    {
        $check = Gate::check('deny potato', ['gelato']);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertFalse($check);
        $this->assertSame(EntryType::GATE, $entry->type);
        $this->assertSame('deny potato', $entry->content['ability']);
        $this->assertSame('denied', $entry->content['result']);
        $this->assertSame(['gelato'], $entry->content['arguments']);
    }
}

class User implements Authenticatable
{
    public $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function getAuthIdentifierName()
    {
        return 'Telescope Test';
    }

    public function getAuthIdentifier()
    {
        return 'telescope-test';
    }

    public function getAuthPassword()
    {
        return 'secret';
    }

    public function getRememberToken()
    {
        return 'i-am-telescope';
    }

    public function setRememberToken($value)
    {
        //
    }

    public function getRememberTokenName()
    {
        //
    }
}
