<?php

namespace Laravel\Telescope\Watchers;

use Exception;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Laravel\Telescope\IncomingDumpEntry;
use Laravel\Telescope\Telescope;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

class DumpWatcher extends Watcher
{
    /**
     * The cache factory implementation.
     */
    protected CacheFactory $cache;

    /**
     * Create a new watcher instance.
     *
     * @return void
     */
    public function __construct(CacheFactory $cache, array $options = [])
    {
        parent::__construct($options);

        $this->cache = $cache;
    }

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $dumpWatcherCache = false;

        try {
            $dumpWatcherCache = $this->cache->get('telescope:dump-watcher');
        } catch (Exception) {
            //
        }

        if (! ($this->options['always'] ?? false) && ! $dumpWatcherCache) {
            return;
        }

        $htmlDumper = new HtmlDumper();
        $htmlDumper->setDumpHeader('');
        $baseDumper = static::getBaseDumper($app);

        VarDumper::setHandler(function ($var) use ($htmlDumper, $baseDumper) {
            $dumper = (! $isNotDumpAndDie = static::isNotDumpAndDie())
                ? $baseDumper
                : $htmlDumper;

            $dump = $dumper->dump((new VarCloner)->cloneVar($var), $isNotDumpAndDie);

            if ($isNotDumpAndDie) {
                $this->recordDump($dump);
            }
        });
    }

    /**
     * Get the base dumper instance.
     */
    protected static function getBaseDumper(Application $app): AbstractDumper
    {
        $baseDumperArgs = [$app->basePath(), $app['config']->get('view.compiled')];

        return in_array(PHP_SAPI, ['cli', 'phpdbg'])
            ? static::getCliDumper(...$baseDumperArgs)
            : static::getHtmlDumper(...$baseDumperArgs);
    }

    protected static function getCliDumper(string $basePath, string $compiledViewPath)
    {
        $foundation = 'Illuminate\Foundation\Console\CliDumper';

        return class_exists($foundation)
            ? new $foundation(new ConsoleOutput, $basePath, $compiledViewPath)
            : new CliDumper;
    }

    protected static function getHtmlDumper(string $basePath, string $compiledViewPath)
    {
        $foundation = 'Illuminate\Foundation\Http\HtmlDumper';

        return class_exists($foundation)
            ? new $foundation(new ConsoleOutput, $basePath, $compiledViewPath)
            : new HtmlDumper;
    }

    /**
     * Determine if the current dump is not a dd() call.
     */
    protected static function isNotDumpAndDie(): bool
    {
        return collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            ->pluck('function')
            ->filter(fn ($value) => $value === 'dd')
            ->isEmpty();
    }

    /**
     * Record a dumped variable.
     */
    public function recordDump(string $dump): void
    {
        Telescope::recordDump(
            IncomingDumpEntry::make(['dump' => $dump])
        );
    }
}
