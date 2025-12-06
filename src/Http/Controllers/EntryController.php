<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\EntryQueryOptions;

abstract class EntryController extends Controller
{
    /**
     * The entry type for the controller.
     *
     * @return string
     */
    abstract protected function entryType();

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    abstract protected function watcher();

    /**
     * List the entries of the given type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, EntriesRepository $storage)
    {
        return response()->json([
            'entries' => $storage->get(
                $this->entryType(),
                EntryQueryOptions::fromRequest($request)
            ),
            'status' => $this->status(),
        ]);
    }

    /**
     * Get an entry with the given ID.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(EntriesRepository $storage, $id)
    {
        $entry = $storage->find($id)->generateAvatar();

        return response()->json([
            'entry' => $entry,
            'batch' => $storage->get(null, EntryQueryOptions::forBatchId($entry->batchId)->limit(-1)),
        ]);
    }

    /**
     * Determine the watcher recording status.
     *
     * @return string
     */
    protected function status()
    {
        if (! config('telescope.enabled', false)) {
            return 'disabled';
        }

        if (cache('telescope:pause-recording', false)) {
            return 'paused';
        }

        $watcher = config('telescope.watchers.'.$this->watcher());

        if (! $watcher || (isset($watcher['enabled']) && ! $watcher['enabled'])) {
            return 'off';
        }

        return 'enabled';
    }

    /**
     * Export an entry with the given ID.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(EntriesRepository $storage, $id)
    {
        $entry = $storage->find($id);

        $data = [
            'id' => $entry->id,
            'batch_id' => $entry->batchId,
            'type' => $entry->type,
            'content' => $entry->content,
            'family_hash' => $entry->familyHash,
            'sequence' => $entry->sequence,
            'created_at' => $entry->createdAt ? $entry->createdAt->toDateTimeString() : null,
            'tags' => $entry->tags ?? [],
        ];

        $filename = "telescope-{$entry->type}-{$entry->id}.json";

        return response(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }
}