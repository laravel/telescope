<?php

namespace Laravel\Telescope\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Laravel\Telescope\Watchers\ExceptionWatcher;
use Laravel\Telescope\Contracts\EntriesRepository;

class ExceptionController extends EntryController
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType()
    {
        return EntryType::EXCEPTION;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher()
    {
        return ExceptionWatcher::class;
    }

    /**
     * Update an entry with the given ID.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(EntriesRepository $storage, Request $request, $id)
    {
        $entry = $storage->find($id);

        if ($request->input('resolved_at') === 'now') {
            $update = new EntryUpdate($entry->id, $entry->type, [
                'resolved_at' => Carbon::now()->toDateTimeString(),
            ]);

            $storage->update(collect([$update]));

            // Reload entry
            $entry = $storage->find($id);
        }

        return response()->json([
            'entry' => $entry,
            'batch' => $storage->get(null, EntryQueryOptions::forBatchId($entry->batchId)->limit(-1)),
        ]);
    }
}
