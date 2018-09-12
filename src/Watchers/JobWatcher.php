<?php

namespace Laravel\Telescope\Watchers;

use ReflectionClass;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Database\Eloquent\Model;
use Laravel\Telescope\ExceptionContext;
use Laravel\Telescope\ExtractProperties;
use Illuminate\Queue\Events\JobProcessed;

class JobWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(JobProcessed::class, [$this, 'recordProcessedJob']);
        $app['events']->listen(JobFailed::class, [$this, 'recordFailedJob']);
    }

    /**
     * Record a queued job was processed.
     *
     * @param  \Illuminate\Queue\Events\JobProcessed  $event
     * @return void
     */
    public function recordProcessedJob(JobProcessed $event)
    {
        $payload = $this->payload($event->job);

        Telescope::recordJob(IncomingEntry::make(array_merge([
            'status' => 'processed',
        ], $this->defaultJobData($event, $payload)))->tags(
            $this->tags($event->job)
        ));
    }

    /**
     * Record a queue job has failed.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function recordFailedJob(JobFailed $event)
    {
        list($payload, $tags) = $this->extractPayloadAndTags($event->job);

        Telescope::recordJob(array_merge(IncomingEntry::make([
            'status' => 'failed',
            'exception' => [
                'message' => $event->exception->getMessage(),
                'trace' => $event->exception->getTrace(),
                'line' => $event->exception->getLine(),
                'line_preview' => ExceptionContext::get($event->exception),
            ],
        ], $this->defaultJobData($event, $payload)))->tags($tags));
    }

    /**
     * Get the default entry data for the given job.
     *
     * @param  mixed  $event
     * @param  array  $payload
     * @return array
     */
    protected function defaultJobData($event, $payload)
    {
        return [
            'id' => $event->job->getJobId(),
            'name' => $event->job->payload()['displayName'],
            'tries' => $event->job->payload()['maxTries'],
            'timeout' => $event->job->payload()['timeout'],
            'queue' => $event->job->getQueue(),
            'connection' => $event->job->getConnectionName(),
            'data' => $payload,
        ];
    }

    /**
     * Extract the payload from the job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return array
     */
    protected function payload($job)
    {
        if (! isset($job->payload()['data']['command'])) {
            return $job->payload()['data'];
        }

        return ExtractProperties::from(
            unserialize($job->payload()['data']['command'])
        );
    }

    /**
     * Extract the tags from the job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return array
     */
    protected function tags($job)
    {
        if (! isset($job->payload()['data']['command'])) {
            return [];
        }

        return ExtractTags::from(
            unserialize($job->payload()['data']['command'])
        );
    }
}
