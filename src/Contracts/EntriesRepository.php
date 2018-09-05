<?php

namespace Laravel\Telescope\Contracts;

use Illuminate\Support\Collection;

interface EntriesRepository
{
    /**
     * Return an entry with the given ID.
     *
     * @param  integer  $id
     * @return mixed
     */
    public function find($id);

    /**
     * Return all the entries of a given type.
     *
     * @param  int  $type
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function get($type, $options = []);

    /**
     * Store the given entries.
     *
     * @param  \Illuminate\Support\Collection  $entries
     * @return mixed
     */
    public function store(Collection $entries);
}
