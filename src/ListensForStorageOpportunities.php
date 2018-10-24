<?php

namespace Laravel\Telescope;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Laravel\Telescope\Contracts\EntriesRepository;

trait ListensForStorageOpportunities
{
    /**
     * An array indicating how many jobs are processing.
     *
     * @var array
     */
    protected static $processingJobs = [];

    /**
     * Register listeners that store the recorded Telescope entries.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public static function listenForStorageOpportunities(Application $app)
    {
        static::storeEntriesBeforeTermination($app);

        static::storeEntriesAfterWorkerLoop($app);
    }

    /**
     * Store the entries in queue before the application termination.
     *
     * This handles storing entries for HTTP requests and Artisan commands.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected static function storeEntriesBeforeTermination(Application $app)
    {
        $app->terminating(function () use ($app) {
            static::store($app->make(EntriesRepository::class));
        });
    }

    /**
     * Store entries after the queue worker loops.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected static function storeEntriesAfterWorkerLoop(Application $app)
    {
        /** @var \Illuminate\Contracts\Events\Dispatcher $dispatcher */
        $dispatcher = $app->make(Dispatcher::class);

        $dispatcher->listen(JobProcessing::class, function () {
            static::startRecording();

            static::$processingJobs[] = true;
        });

        $dispatcher->listen(JobProcessed::class, function () use ($app) {
            static::storeIfDoneProcessingJob($app);
        });

        $dispatcher->listen(JobFailed::class, function () use ($app) {
            static::storeIfDoneProcessingJob($app);
        });

        $dispatcher->listen(JobExceptionOccurred::class, function () {
            array_pop(static::$processingJobs);
        });
    }

    /**
     * Store the recorded entries if totally done processing the current job.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected static function storeIfDoneProcessingJob(Application $app)
    {
        array_pop(static::$processingJobs);

        if (empty(static::$processingJobs)) {
            static::store($app->make(EntriesRepository::class));

            static::stopRecording();
        }
    }
}
