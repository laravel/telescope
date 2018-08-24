<?php

namespace Laravel\Telescope\Watchers;

use Throwable;
use Whoops\Exception\Inspector;
use Laravel\Telescope\Telescope;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;

class QueueWatcher extends AbstractWatcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(JobProcessed::class, [$this, 'recordJobProcessed']);
        $app['events']->listen(JobFailed::class, [$this, 'recordJobFailed']);
    }

    /**
     * Record a queue job was processed.
     *
     * @param \Illuminate\Queue\Events\JobProcessed $event
     * @return void
     */
    public function recordJobProcessed(JobProcessed $event)
    {
        Telescope::record(4, [
            'id' => $event->job->getJobId(),
            'status' => 'processed',
            'name' => $event->job->payload()['displayName'],
            'tries' => $event->job->payload()['maxTries'],
            'timeout' => $event->job->payload()['timeout'],
            'queue' => $event->job->getQueue(),
            'connection' => $event->job->getConnectionName(),
            'data' => $this->formatJobData($event->job),
        ]);
    }

    /**
     * Record a queue job has failed.
     *
     * @param \Illuminate\Queue\Events\JobFailed $event
     * @return void
     */
    public function recordJobFailed(JobFailed $event)
    {
        Telescope::record(4, [
            'id' => $event->job->getJobId(),
            'status' => 'failed',
            'exception' => [
                'message' => $event->exception->getMessage(),
                'trace' => $event->exception->getTrace(),
                'line' => $event->exception->getLine(),
                'line_preview' => $this->formatExceptionLinePreview($event->exception),
            ],
            'name' => $event->job->payload()['displayName'],
            'tries' => $event->job->payload()['maxTries'],
            'timeout' => $event->job->payload()['timeout'],
            'queue' => $event->job->getQueue(),
            'connection' => $event->job->getConnectionName(),
            'data' => $this->formatJobData($event->job),
        ]);
    }

    /**
     * @param  \Illuminate\Queue\Jobs\Job $job
     * @return mixed
     */
    private function formatJobData($job)
    {
        if (! isset($job->payload()['data']['command'])) {
            return $job->payload()['data'];
        }

        return json_decode(json_encode(unserialize($job->payload()['data']['command'])), true);
    }

    /**
     * Format the exception line preview.
     *
     * @param  Throwable $exception
     * @return mixed
     */
    private function formatExceptionLinePreview(Throwable $exception)
    {
        return (new Inspector($exception))
            ->getFrames()[0]
            ->getFileLines($exception->getLine() - 10, 20);
    }
}