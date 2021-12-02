<?php

namespace Laravel\Telescope;

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
     * @var \Carbon\CarbonInterface|\Carbon\Carbon
     */
    public $createdAt;

    /**
     * The tags assigned to the entry.
     *
     * @var array
     */
    private $tags;

    /**
     * The generated URL to the entry user's avatar.
     *
     * @var string
     */
    protected $avatar;

    /**
     * Create a new entry result instance.
     *
     * @param  mixed  $id
     * @param  mixed  $sequence
     * @param  string  $batchId
     * @param  string  $type
     * @param  string|null  $familyHash
     * @param  array  $content
     * @param  \Carbon\CarbonInterface|\Carbon\Carbon  $createdAt
     * @param  array  $tags
     */
    public function __construct($id, $sequence, string $batchId, string $type, ?string $familyHash, array $content, $createdAt, $tags = [])
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
     * Set the URL to the entry user's avatar.
     *
     * @return $this
     */
    public function generateAvatar()
    {
        $this->avatar = Avatar::url($this->content['user'] ?? []);

        return $this;
    }

    /**
     * Get the array representation of the entry.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return collect([
            'id' => $this->id,
            'sequence' => $this->sequence,
            'batch_id' => $this->batchId,
            'type' => $this->type,
            'content' => $this->content,
            'tags' => $this->tags,
            'family_hash' => $this->familyHash,
            'created_at' => $this->createdAt->toDateTimeString(),
        ])->when($this->avatar, function ($items) {
            return $items->mergeRecursive([
                'content' => [
                    'user' => [
                        'avatar' => $this->avatar,
                    ],
                ],
            ]);
        })->all();
    }
}
