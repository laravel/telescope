<?php

namespace Laravel\Telescope;

class Entry
{
    /**
     * The entry's batch ID.
     *
     * @var string
     */
    public $batchId;

    /**
     * The entry's type.
     *
     * @var int
     */
    public $type;

    /**
     * The entry's data.
     *
     * @var array
     */
    public $data = [];

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
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->recordedAt = now();
    }

    /**
     * Create a new entry instance.
     *
     * @param  array  $data
     * @return void
     */
    public static function make(array $data)
    {
        return new static($data);
    }

    /**
     * Assign the entry a given batch ID.
     *
     * @param  string  $batchId
     * @return $this
     */
    public function assignToBatch(string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    /**
     * Assign the entry a given type.
     *
     * @param  int  $type
     * @return $this
     */
    public function asType(int $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Merge tags into the entry's existing tags.
     *
     * @param  array  $tags
     * @return $this
     */
    public function withTags(array $tags)
    {
        $this->tags = array_unique(array_merge($this->tags, $tags));

        return $this;
    }

    /**
     * Get an array representation of the entry.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'batch_id' => $this->batchId,
            'type' => $this->type,
            'content' => json_encode($this->data),
            'created_at' => $this->recordedAt->toDateTimeString(),
        ];

    }
}
