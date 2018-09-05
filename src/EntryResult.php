<?php

namespace Laravel\Telescope;

use JsonSerializable;
use DateTimeInterface;

class EntryResult implements JsonSerializable
{
    public $id;
    public $batchId;
    public $type;
    public $content = [];
    public $createdAt;

    /**
     * Create a new entry result instance.
     *
     * @param  mixed  $id
     * @param  string  $batchId
     * @param  int  $type
     * @param  array  $content
     * @param  \DateTimeInterface  $createdAt
     */
    public function __construct($id, string $batchId, int $type, array $content, DateTimeInterface $createdAt)
    {
        $this->id = $id;
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
            'batch_id' => $this->batchId,
            'type' => $this->type,
            'content' => $this->content,
            'created_at' => $this->createdAt->toDateTimeString(),
        ];
    }
}
