<?php

namespace Laravel\Telescope\Watchers;

use Throwable;
use ReflectionClass;
use Whoops\Exception\Inspector;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Database\Eloquent\Model;
use Laravel\Telescope\ExceptionContext;
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
        list($payload, $tags) = $this->extractPayloadAndTags($event->job);

        $tags[] = 'processed';

        Telescope::recordJob(IncomingEntry::make(array_merge([
            'status' => 'processed',
        ], $this->defaultJobData($event, $payload)))->tags($tags));
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
     * Extract the payload and tags from the job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return array
     */
    private function extractPayloadAndTags($job)
    {
        if (! isset($job->payload()['data']['command'])) {
            return [$job->payload()['data'], []];
        }

        $tags = [];

        $command = unserialize($job->payload()['data']['command']);

        $payload = collect((new ReflectionClass($command))->getProperties())
            ->mapWithKeys(function ($property) use ($command, &$tags) {
                $property->setAccessible(true);

                if (($value = $property->getValue($command)) instanceof Model) {
                    $tags[] = $model = get_class($value).':'.$value->getKey();

                    return [$property->getName() => $model];
                } elseif (is_object($value)) {
                    return [
                        $property->getName() => [
                            'class' => get_class($value),
                            'properties' => json_decode(json_encode($value), true)
                        ]
                    ];
                } else {
                    return [$property->getName() => json_decode(json_encode($value), true)];
                }
            })->toArray();

        return [$payload, $tags];
    }

    /**
     * Extract tags from the given job.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  bool  $processed
     * @return array
     */
    private function extractTagsFromJob($job, $processed = true)
    {
        $tags = [
            $job->payload()['displayName'],
        ];

        if (isset($job->payload()['data']['command'])) {
            $command = unserialize($job->payload()['data']['command']);

            foreach ((new ReflectionClass($command))->getProperties() as $property) {
                $property->setAccessible(true);

                if (($value = $property->getValue($command)) instanceof Model) {
                    $tags[] = get_class($value).':'.$value->getKey();
                }
            }
        }

        return $tags;
    }
}
