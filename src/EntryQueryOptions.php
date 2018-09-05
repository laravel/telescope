<?php

namespace Laravel\Telescope;

use Illuminate\Http\Request;

class EntryQueryOptions
{
    /**
     * The batch ID that entries should belong to.
     *
     * @var string
     */
    public $batchId;

    /**
     * The tag that must belong to retrieved entries.
     *
     * @var string
     */
    public $tag;

    /**
     * The ID that all retrieved entries should be less than.
     *
     * @var mixed
     */
    public $beforeId;

    /**
     * The number of entries to retrieve.
     *
     * @var int
     */
    public $limit = 50;

    /**
     * Create new entry query options from the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return static
     */
    public static function fromRequest(Request $request)
    {
        return (new static)
                ->batchId($request->batch_id)
                ->beforeId($request->before)
                ->tag($request->tag)
                ->limit($request->take ?? 50);
    }

    /**
     * Create new entry query options for the given batch ID.
     *
     * @param  string  $batchId
     * @return static
     */
    public static function forBatchId(?string $batchId)
    {
        return (new static)->batchId($batchId);
    }

    /**
     * Set the batch ID for the query.
     *
     * @param  string  $batchId
     * @return $this
     */
    public function batchId(?string $batchId)
    {
        $this->batchId = $batchId;

        return $this;
    }

    /**
     * Set the ID that all retrieved entries should be less than.
     *
     * @param  mixed  $id
     * @return $this
     */
    public function beforeId($id)
    {
        $this->beforeId = $id;

        return $this;
    }

    /**
     * Set the tag that must belong to retrieved entries.
     *
     * @param  string  $tag
     * @return $this
     */
    public function tag(?string $tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Set the number of entries that should be retrieved.
     *
     * @param  string  $limit
     * @return $this
     */
    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }
}
