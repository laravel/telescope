<?php

namespace Laravel\Telescope;

use JsonSerializable;
use DateTimeInterface;

class EntryResult implements JsonSerializable
{
    /**
     * The entry's primary key.
     *
     * @var mixed
     */
    public $id;

    /**
     * The entry's sequence.
     *
     * @var mixed
     */
    public $sequence;

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
     * The entry's content.
     *
     * @var array
     */
    public $content = [];

    /**
     * The datetime that the entry was recorded.
     *
     * @var \DateTimeInterface
     */
    public $createdAt;

    /**
     * The tags assigned to the entry.
     *
     * @var array
     */
    private $tags;

    /**
     * Create a new entry result instance.
     *
     * @param  mixed  $id
     * @param  string  $batchId
     * @param  string  $type
     * @param  array  $content
     * @param  \DateTimeInterface  $createdAt
     * @param  int  $occurrences
     * @param  string  $group
     */
    public function __construct($id, $sequence, string $batchId, string $type, array $content, DateTimeInterface $createdAt, $tags = [], $occurrences = 1, $group = null)
    {
        $this->id = $id;
        $this->sequence = $sequence;
        $this->type = $type;
        $this->batchId = $batchId;
        $this->content = $content;
        $this->createdAt = $createdAt;
        $this->tags = $tags;
        $this->occurrences = $occurrences;
        $this->group = $group;
    }

    /**
     * Get the array reprentation of the entry.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'batch_id' => $this->batchId,
            'type' => $this->type,
            'content' => $this->content,
            'tags' => $this->tags,
            'occurrences' => $this->occurrences,
            'group' => $this->group,
            'created_at' => $this->createdAt->toDateTimeString(),
        ];
    }
}
