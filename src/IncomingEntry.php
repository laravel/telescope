<?php

namespace Laravel\Telescope;

use Illuminate\Support\Str;

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
     * Create a new entry instance.
     *
     * @param  array  $content
     * @return void
     */
    public function __construct(array $content)
    {
        $this->uuid = Str::uuid();
        
        $this->recordedAt = now();

        $this->content = array_merge($content, ['hostname' => gethostname()]);

        $this->tags = ['hostname:'.gethostname()];
    }

    /**
     * Create a new entry instance.
     *
     * @param  array  $content
     * @return static
     */
    public static function make(array $content)
    {
        return (new static($content));
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
     * @param  mixed  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        $this->content = array_merge($this->content, [
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ],
        ]);

        $this->tags(['currentUser:'.$user->getKey()]);

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
