<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class DumpWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        VarDumper::setHandler(function ($var) {
            $this->recordDump((new HtmlDumper)->dump(
                (new VarCloner)->cloneVar($var), true
            ));
        });
    }

    /**
     * Record a dumped variable.
     *
     * @param  string  $dump
     * @return void
     */
    public function recordDump($dump)
    {
        Telescope::recordDump(
            IncomingEntry::make(['dump' => $dump])
        );
    }
}
