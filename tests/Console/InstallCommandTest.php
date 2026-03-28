<?php

namespace Laravel\Telescope\Tests\Console;

use Laravel\Telescope\TelescopeServiceProvider;
use Orchestra\Testbench\TestCase;

class InstallCommandTest extends TestCase
{
    /**
     * A temporary directory to use as the base application path during tests.
     */
    protected static string $tempBasePath;

    /** {@inheritdoc} */
    #[\Override]
    protected function setUp(): void
    {
        static::$tempBasePath = sys_get_temp_dir().'/telescope_test_'.uniqid();

        mkdir(static::$tempBasePath.'/database/migrations', 0755, true);
        mkdir(static::$tempBasePath.'/config', 0755, true);
        mkdir(static::$tempBasePath.'/app/Providers', 0755, true);
        mkdir(static::$tempBasePath.'/bootstrap', 0755, true);
        file_put_contents(static::$tempBasePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

        parent::setUp();
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteDirectory(static::$tempBasePath);
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function getPackageProviders($app)
    {
        return [
            TelescopeServiceProvider::class,
        ];
    }

    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        $app->useAppPath(static::$tempBasePath.'/app');
        $app->useConfigPath(static::$tempBasePath.'/config');
        $app->useDatabasePath(static::$tempBasePath.'/database');
        $app->useBootstrapPath(static::$tempBasePath.'/bootstrap');
    }

    public function test_install_publishes_migrations_when_none_exist()
    {
        $this->artisan('telescope:install');

        $migrations = glob(static::$tempBasePath.'/database/migrations/*_create_telescope_entries_table.php');

        $this->assertNotEmpty($migrations, 'Migration file should be published when no existing migration is found.');
    }

    public function test_install_skips_migrations_when_already_published()
    {
        // Simulate an existing migration file.
        $existingMigration = static::$tempBasePath.'/database/migrations/2024_01_01_000000_create_telescope_entries_table.php';
        file_put_contents($existingMigration, '<?php // existing migration');

        $this->artisan('telescope:install');

        $migrations = glob(static::$tempBasePath.'/database/migrations/*_create_telescope_entries_table.php');

        $this->assertCount(1, $migrations, 'No additional migration file should be published when one already exists.');
        $this->assertStringContainsString('existing migration', file_get_contents($migrations[0]));
    }

    public function test_install_publishes_migrations_when_directory_does_not_exist()
    {
        // Remove the migrations directory entirely.
        rmdir(static::$tempBasePath.'/database/migrations');

        $this->artisan('telescope:install');

        $migrations = glob(static::$tempBasePath.'/database/migrations/*_create_telescope_entries_table.php');

        $this->assertNotEmpty($migrations, 'Migration file should be published when migrations directory does not exist.');
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
