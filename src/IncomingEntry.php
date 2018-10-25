<?php

namespace Laravel\Telescope;

use Illuminate\Support\Str;
use Laravel\Telescope\Contracts\EntriesRepository;

class IncomingEntry
{
    /**
     * The entry's UUID.
     *
     * @var string
     */
    public $uuid;

    /**
     * The entry's batch ID.
     *
     * @var string
     */
    public $batchId;

    /**
     * The entry's type.
     *
     * @var string
     */
    public $type;

    /**
     * The entry's family hash.
     *
     * @var string|null
     */
    public $familyHash;

    /**
     * The currently authenticated user, if applicable.
     *
     * @var mixed
     */
    public $user;

    /**
     * The entry's content.
     *
     * @var array
     */
    public $content = [];

    /**
     * The entry's tags.
     *
     * @var array
     */
    public $tags = [];

    /**
     * The DateTime that indicates when the entry was recorded.
     *
     * @var \DateTimeInterface
     */
    public $recordedAt;

    /**
     * Create a new incoming entry instance.
     *
     * @param  array  $content
     * @return void
     */
    public function __construct(array $content)
    {
        $this->uuid = Str::orderedUuid();

        $this->recordedAt = now();

        $this->content = array_merge($content, ['hostname' => gethostname()]);

        // $this->tags = ['hostname:'.gethostname()];
    }

    /**
     * Create a new entry instance.
     *
     * @param  mixed  ...$arguments
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Assign the entry a given batch ID.
     *
     * @param  string  $batchId
     * @return $this
     */
    public function batchId(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    /**
     * Assign the entry a given type.
     *
     * @param  string  $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the currently authenticated user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        $this->content = array_merge($this->content, [
            'user' => [
                'id' => $user->getAuthIdentifier(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ],
        ]);

        $this->tags(['Auth:'.$user->getAuthIdentifier()]);

        return $this;
    }

    /**
     * Merge tags into the entry's existing tags.
     *
     * @param  array  $tags
     * @return $this
     */
    public function tags(array $tags)
    {
        $this->tags = array_unique(array_merge($this->tags, $tags));

        return $this;
    }

    /**
     * Determine if the incoming entry has a monitored tag.
     *
     * @return bool
     */
    public function hasMonitoredTag()
    {
        if (! empty($this->tags)) {
            return app(EntriesRepository::class)->isMonitoring($this->tags);
        }

        return false;
    }

    /**
     * Determine if the incoming entry is a failed job.
     *
     * @return bool
     */
    public function isFailedJob()
    {
        return $this->type === EntryType::JOB &&
               ($this->content['status'] ?? null) === 'failed';
    }

    /**
     * Determine if the incoming entry is a reportable exception.
     *
     * @return bool
     */
    public function isReportableException()
    {
        return false;
    }

    /**
     * Determine if the incoming entry is an exception.
     *
     * @return bool
     */
    public function isException()
    {
        return false;
    }

    /**
     * Determine if the incoming entry is a dump.
     *
     * @return bool
     */
    public function isDump()
    {
        return false;
    }

    /**
     * Determine if the incoming entry is a scheduled task.
     *
     * @return bool
     */
    public function isScheduledTask()
    {
        return $this->type === EntryType::SCHEDULED_TASK;
    }

    /**
     * Get the family look-up hash for the incoming entry.
     *
     * @return string|null
     */
    public function familyHash()
    {
        return $this->familyHash;
    }

    /**
     * Get an array representation of the entry for storage.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'uuid' => $this->uuid,
            'batch_id' => $this->batchId,
            'type' => $this->type,
            'content' => $this->content,
            'created_at' => $this->recordedAt->toDateTimeString(),
        ];
    }
}
