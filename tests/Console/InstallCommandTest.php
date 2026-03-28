<?php

namespace Laravel\Telescope\Tests\Console;

use Laravel\Telescope\Console\InstallCommand;
use Orchestra\Testbench\TestCase;

class InstallCommandTest extends TestCase
{
    /**
     * A temporary directory to use as the database path during tests.
     */
    protected static string $tempDatabasePath;

    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        static::$tempDatabasePath = sys_get_temp_dir().'/telescope_test_'.uniqid();

        mkdir(static::$tempDatabasePath.'/migrations', 0755, true);

        parent::setUp();
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteDirectory(static::$tempDatabasePath);
    }

    public function test_migration_exists_returns_false_when_no_migration_present()
    {
        $command = new InstallCommand;

        $method = new \ReflectionMethod($command, 'migrationExists');

        $this->app->useDatabasePath(static::$tempDatabasePath);

        $this->assertFalse($method->invoke($command, 'create_telescope_entries_table'));
    }

    public function test_migration_exists_returns_true_when_migration_present()
    {
        $command = new InstallCommand;

        $method = new \ReflectionMethod($command, 'migrationExists');

        file_put_contents(
            static::$tempDatabasePath.'/migrations/2024_01_01_000000_create_telescope_entries_table.php',
            '<?php // existing migration'
        );

        $this->app->useDatabasePath(static::$tempDatabasePath);

        $this->assertTrue($method->invoke($command, 'create_telescope_entries_table'));
    }

    public function test_migration_exists_returns_false_when_directory_does_not_exist()
    {
        $command = new InstallCommand;

        $method = new \ReflectionMethod($command, 'migrationExists');

        // Point to a non-existent directory.
        rmdir(static::$tempDatabasePath.'/migrations');

        $this->app->useDatabasePath(static::$tempDatabasePath);

        $this->assertFalse($method->invoke($command, 'create_telescope_entries_table'));
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($directory);
    }
}
