<?php

namespace Laravel\Telescope;

use Carbon\Carbon;
use JsonSerializable;

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
     * The entry's family hash.
     *
     * @var string|null
     */
    public $familyHash;

    /**
     * The entry's content.
     *
     * @var array
     */
    public $content = [];

    /**
     * The datetime that the entry was recorded.
     *
     * @var Carbon
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
     * @param  mixed  $sequence
     * @param  string  $batchId
     * @param  string  $type
     * @param  string|null  $familyHash
     * @param  array  $content
     * @param  \Carbon\Carbon  $createdAt
     * @param  array  $tags
     */
    public function __construct($id, $sequence, string $batchId, string $type, ?string $familyHash, array $content, Carbon $createdAt, $tags = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->tags = $tags;
        $this->batchId = $batchId;
        $this->content = $content;
        $this->sequence = $sequence;
        $this->createdAt = $createdAt;
        $this->familyHash = $familyHash;
    }

    /**
     * Get the array representation of the entry.
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
            'family_hash' => $this->familyHash,
            'created_at' => $this->createdAt->toDateTimeString(),
        ];
    }
}
