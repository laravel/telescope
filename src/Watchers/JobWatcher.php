<?php

namespace Laravel\Telescope\Watchers;

use Illuminate\Bus\BatchRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Queue;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\ExceptionContext;
use Laravel\Telescope\ExtractProperties;
use Laravel\Telescope\ExtractTags;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use RuntimeException;

class JobWatcher extends Watcher
{
    /**
     * The list of ignored jobs classes.
     *
     * @var array<int, class-string>
     */
    protected $ignoredJobClasses = [
        \Laravel\Scout\Jobs\MakeSearchable::class, // @phpstan-ignore-line
        \Laravel\Telescope\Jobs\ProcessPendingUpdates::class,
    ];

    /**
     * Register the watcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function register($app)
    {
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            return ['telescope_uuid' => optional($this->recordJob($connection, $queue, $payload))->uuid];
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
     * @return \Laravel\Telescope\IncomingEntry|null
     */
    public function recordJob($connection, $queue, array $payload)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $job = isset($payload['data']['command'])
            ? get_class($payload['data']['command'])
            : $payload['job'];

        if (in_array($job, $this->ignoredJobClasses)) {
            return;
        }

        $content = array_merge([
            'status' => 'pending',
        ], $this->defaultJobData($connection, $queue, $payload, $this->data($payload)));

        Telescope::recordJob(
            $entry = IncomingEntry::make($content)
                        ->withFamilyHash($content['data']['batchId'] ?? null)
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
        if (! Telescope::isRecording()) {
            return;
        }

        $uuid = $event->job->payload()['telescope_uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        Telescope::recordUpdate(EntryUpdate::make(
            $uuid, EntryType::JOB, ['status' => 'processed']
        ));

        $this->updateBatch($event->job->payload());
    }

    /**
     * Record a queue job has failed.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function recordFailedJob(JobFailed $event)
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $uuid = $event->job->payload()['telescope_uuid'] ?? null;

        if (! $uuid) {
            return;
        }

        Telescope::recordUpdate(EntryUpdate::make(
            $uuid, EntryType::JOB, [
                'status' => 'failed',
                'exception' => [
                    'message' => $event->exception->getMessage(),
                    'trace' => collect($event->exception->getTrace())->map(fn ($trace) => Arr::except($trace, ['args']))->all(),
                    'line' => $event->exception->getLine(),
                    'line_preview' => ExceptionContext::get($event->exception),
                ],
            ]
        )->addTags(['failed']));

        $this->updateBatch($event->job->payload());
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

    /**
     * Update the batch.
     *
     * @param  array  $payload
     * @return void
     */
    protected function updateBatch($payload)
    {
        if (! isset($payload['data']['command'])) {
            return;
        }

        $wasRecordingEnabled = Telescope::$shouldRecord;

        Telescope::$shouldRecord = false;

        $command = $this->getCommand($payload['data']);

        if ($wasRecordingEnabled) {
            Telescope::$shouldRecord = true;
        }

        $properties = ExtractProperties::from(
            $command
        );

        if (isset($properties['batchId'])) {
            $batch = app(BatchRepository::class)->find($properties['batchId']);

            if (is_null($batch)) {
                return;
            }

            Telescope::recordUpdate(EntryUpdate::make(
                $properties['batchId'], EntryType::BATCH, $batch->toArray()
            ));
        }
    }

    /**
     * Get the command from the given payload.
     *
     * @param  array  $data
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function getCommand(array $data)
    {
        if (Str::startsWith($data['command'], 'O:')) {
            return unserialize($data['command']);
        }

        if (app()->bound(Encrypter::class)) {
            return unserialize(app(Encrypter::class)->decrypt($data['command']));
        }

        throw new RuntimeException('Unable to extract job payload.');
    }
}
