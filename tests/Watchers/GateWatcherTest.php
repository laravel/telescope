<?php

namespace Laravel\Telescope\Tests\Watchers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\Watchers\GateWatcher;
use Laravel\Telescope\Tests\FeatureTestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GateWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            GateWatcher::class => true,
        ]);

        $app->setBasePath(dirname(__FILE__, 3));

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
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(40, $entry->content['line']);
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
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(55, $entry->content['line']);
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
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(70, $entry->content['line']);
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
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(85, $entry->content['line']);
        $this->assertSame('deny potato', $entry->content['ability']);
        $this->assertSame('denied', $entry->content['result']);
        $this->assertSame(['gelato'], $entry->content['arguments']);
    }

    public function test_gate_watcher_registers_allowed_policy_entries()
    {
        Gate::policy(TestResource::class, TestPolicy::class);

        (new TestController())->create(new TestResource());

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::GATE, $entry->type);
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(186, $entry->content['line']);
        $this->assertSame('create', $entry->content['ability']);
        $this->assertSame('allowed', $entry->content['result']);
        $this->assertSame([[]], $entry->content['arguments']);
    }

    public function test_gate_watcher_registers_denied_policy_entries()
    {
        Gate::policy(TestResource::class, TestPolicy::class);

        try {
            (new TestController())->update(new TestResource());
        } catch (\Exception $ex) {
            // ignore
        }

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::GATE, $entry->type);
        $this->assertSame(__FILE__, $entry->content['file']);
        $this->assertSame(191, $entry->content['line']);
        $this->assertSame('update', $entry->content['ability']);
        $this->assertSame('denied', $entry->content['result']);
        $this->assertSame([[]], $entry->content['arguments']);
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

class TestResource
{
    //
}

class TestController
{
    use AuthorizesRequests;

    public function create($object)
    {
        $this->authorize($object);
    }

    public function update($object)
    {
        $this->authorize($object);
    }
}

class TestPolicy
{
    public function create(?User $user)
    {
        return true;
    }

    public function update(?User $user)
    {
        return false;
    }
}
