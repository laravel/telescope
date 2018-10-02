<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Laravel\Telescope\Contracts\EntriesRepository;

class QueueController extends EntryController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType()
    {
        return EntryType::JOB;
    }

    /**
     * Get an entry with the given ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, EntriesRepository $storage, $id)
    {
        $entry = $storage->find($id);

        return response()->json([
            'entry' => $entry,
            'batch' => isset($entry->content['updated_batch_id'])
                            ? $storage->get(null, EntryQueryOptions::forBatchId($entry->content['updated_batch_id']))
                            : null,
        ]);
    }
}
