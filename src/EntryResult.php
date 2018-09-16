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
     * Create a new entry result instance.
     *
     * @param  mixed  $id
     * @param  string  $batchId
     * @param  string  $type
     * @param  array  $content
     * @param  \DateTimeInterface  $createdAt
     */
    public function __construct($id, $sequence, string $batchId, string $type, array $content, DateTimeInterface $createdAt)
    {
        $this->id = $id;
        $this->sequence = $sequence;
        $this->type = $type;
        $this->batchId = $batchId;
        $this->content = $content;
        $this->createdAt = $createdAt;
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
            'created_at' => $this->createdAt->toDateTimeString(),
        ];
    }
}
