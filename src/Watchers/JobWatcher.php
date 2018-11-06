<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Queue\Queue;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Queue\Events\JobFailed;
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
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['telescope_uuid' => $this->recordJob($connection, $queue, $payload)->uuid];
        });

        $app['events']->listen(JobProcessed::class, [$this, 'recordProcessedJob']);
        $app['events']->listen(JobFailed::class, [$this, 'recordFailedJob']);
    }

    /**
     * Record a job being created.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  array  $payload
     * @return \Laravel\Telescope\IncomingEntry
     */
    public function recordJob($connection, $queue, array $payload)
    {
        $content = array_merge([
            'status' => 'pending',
        ], $this->defaultJobData($connection, $queue, $payload, $this->data($payload)));

        Telescope::recordJob(
            $entry = IncomingEntry::make($content)
                        ->tags($this->tags($payload))
        );

        return $entry;
    }

    /**
     * Record a queued job was processed.
     *
     * @param  \Illuminate\Queue\Events\JobProcessed  $event
     * @return void
     */
    public function recordProcessedJob(JobProcessed $event)
    {
        $uuid = $event->job->payload()['telescope_uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        Telescope::recordUpdate(EntryUpdate::make(
            $uuid, EntryType::JOB, ['status' => 'processed']
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
        $uuid = $event->job->payload()['telescope_uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        Telescope::recordUpdate(EntryUpdate::make(
            $uuid, EntryType::JOB, [
                'status' => 'failed',
                'exception' => [
                    'message' => $event->exception->getMessage(),
                    'trace' => $event->exception->getTrace(),
                    'line' => $event->exception->getLine(),
                    'line_preview' => ExceptionContext::get($event->exception),
                ],
            ]
        )->addTags(['failed']));
    }

    /**
     * Get the default entry data for the given job.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  array  $payload
     * @param  array  $data
     * @return array
     */
    protected function defaultJobData($connection, $queue, array $payload, array $data)
    {
        return [
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload['displayName'],
            'tries' => $payload['maxTries'],
            'timeout' => $payload['timeout'],
            'data' => $data,
        ];
    }

    /**
     * Extract the job "data" from the job payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function data(array $payload)
    {
        if (! isset($payload['data']['command'])) {
            return $payload['data'];
        }

        return ExtractProperties::from(
            $payload['data']['command']
        );
    }

    /**
     * Extract the tags from the job payload.
     *
     * @param  array  $payload
     * @return array
     */
    protected function tags(array $payload)
    {
        if (! isset($payload['data']['command'])) {
            return [];
        }

        return ExtractTags::fromJob(
            $payload['data']['command']
        );
    }
}
