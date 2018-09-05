<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Throwable;
use Whoops\Exception\Inspector;
use Laravel\Telescope\Telescope;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;

class QueueWatcher extends Watcher
{
    /**
     * Register the watcher.
     *
     * @param  $app \Illuminate\Contracts\Foundation\Application
     * @return void
     */
    public function register($app)
    {
        $app['events']->listen(JobProcessed::class, [$this, 'recordProcessedJob']);
        $app['events']->listen(JobFailed::class, [$this, 'recordFailedJob']);
    }

    /**
     * Record a queue job was processed.
     *
     * @param \Illuminate\Queue\Events\JobProcessed $event
     * @return void
     */
    public function recordProcessedJob(JobProcessed $event)
    {
        list($payload, $tags) = $this->extractPayloadAndTags($event->job);

        $tags[] = 'processed';

        Telescope::recordJob([
            'id' => $event->job->getJobId(),
            'status' => 'processed',
            'name' => $event->job->payload()['displayName'],
            'tries' => $event->job->payload()['maxTries'],
            'timeout' => $event->job->payload()['timeout'],
            'queue' => $event->job->getQueue(),
            'connection' => $event->job->getConnectionName(),
            'data' => $payload,
        ], $tags);
    }

    /**
     * Record a queue job has failed.
     *
     * @param \Illuminate\Queue\Events\JobFailed $event
     * @return void
     */
    public function recordFailedJob(JobFailed $event)
    {
        list($payload, $tags) = $this->extractPayloadAndTags($event->job);

        $tags[] = 'failed';

        Telescope::recordJob([
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
            'data' => $payload,
        ], $tags);
    }

    /**
     * Extract the payload and tags from the job.
     *
     * @param  \Illuminate\Contracts\Queue\Job $job
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

    /**
     * Extract tags from the given job.
     *
     * @param  \Illuminate\Contracts\Queue\Job $job
     * @param  bool $processed
     * @return array
     */
    private function extractTagsFromJob($job, $processed = true)
    {
        $tags = [
            ($processed ? 'processed' : 'failed'),
            $job->payload()['displayName'],
            'queue:'.$job->getQueue(),
            'connection:'.$job->getConnectionName(),
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
