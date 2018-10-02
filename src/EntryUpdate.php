<?php

namespace Laravel\Telescope;

class EntryUpdate
{
    /**
     * The entry's UUID.
     *
     * @var string
     */
    public $uuid;

    /**
     * The entry's type.
     *
     * @var string
     */
    public $type;

    /**
     * The properties that should be updated on the entry.
     *
     * @var array
     */
    public $changes = [];

    /**
     * The changes to be applied on the tags.
     *
     * @var array
     */
    public $tagsChanges = ['removed' => [], 'added' => []];

    /**
     * Create a new incoming entry instance.
     *
     * @param  string  $uuid
     * @param  string  $type
     * @param  array  $content
     * @return void
     */
    public function __construct($uuid, $type, array $changes)
    {
        $this->uuid = $uuid;
        $this->type = $type;
        $this->changes = $changes;
    }

    /**
     * Create a new entry update instance.
     *
     * @param  mixed  ...$arguments
     * @return static
     */
    public static function make(...$arguments)
    {
        return (new static(...$arguments));
    }

    /**
     * Add tags to the entry.
     *
     * @param  array  $tags
     * @return $this
     */
    public function addTags(array $tags){
        $this->tagsChanges['added'] = array_unique(
            array_merge($this->tagsChanges['added'], $tags)
        );

        return $this;
    }
}
