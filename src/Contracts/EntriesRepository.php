<?php

namespace Laravel\Telescope\Contracts;

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
     * @param  array  $data
     * @param  string  $batch
     * @return mixed
     */
    public function store($data, $batch);
}
