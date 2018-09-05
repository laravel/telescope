<?php

namespace Laravel\Telescope\Contracts;

use Illuminate\Support\Collection;
use Laravel\Telescope\Storage\EntryQueryOptions;

interface EntriesRepository
{
    /**
     * Return an entry with the given ID.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function find($id);

    /**
     * Return all the entries of a given type.
     *
     * @param  int  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Support\Collection
     */
    public function get($type, EntryQueryOptions $options = null);

    /**
     * Store the given entries.
     *
     * @param  \Illuminate\Support\Collection  $entries
     * @return void
     */
    public function store(Collection $entries);
}
