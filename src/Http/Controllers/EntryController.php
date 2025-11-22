<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Illuminate\Support\Facades\Http;

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

    public function copyCurl(EntriesRepository $storage, $id)
    {
        $entry = $storage->find($id);

        if (! $entry) {
            abort(404);
        }

        $method = strtoupper($entry->content['method']);
        $url = url($entry->content['uri']);
        $headers = $entry->content['headers'] ?? [];
        $payload = $entry->content['payload'] ?? null;

        $curl = "curl -X {$method}";

        foreach ($headers as $key => $value) {
            $v = is_array($value) ? implode(', ', $value) : $value;
            $curl .= " -H " . escapeshellarg("$key: $v");
        }

        if (! empty($payload)) {
            if (is_array($payload)) {
                $payload = json_encode($payload);
            }

            $curl .= " --data " . escapeshellarg($payload);
        }

        $curl .= " $url";

        return response()->json([
            'curl' => $curl
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
}
