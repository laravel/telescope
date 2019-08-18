<?php

namespace Laravel\Telescope\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Watchers\LogWatcher;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Laravel\Telescope\Contracts\EntriesRepository;

class TimingController extends Controller
{
    /**
     * Get an entry with the given ID.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(EntriesRepository $storage, $id)
    {
        $entry = $storage->find($id);

        $start = 0;
        $end = 0;
        $batch = $storage->get(null, EntryQueryOptions::forBatchId($entry->batchId)->limit(-1))
            ->map(function(EntryResult $entry) {

                $timeEnd = $entry->content['timeEnd'] ?? null;
                if ($entry->content['timeEnd'] === null) {
                    return;
                }

                list($timeStart, $timeEnd) = $this->getTimesForEntry($entry);

                $label = $this->getLabelForEntry($entry);
                    
                return [
                    'id' => $entry->id,
                    'entryType' => $entry->type,
                    'label' => $this->getLabelForEntry($entry),
                    'timeStart' => $timeStart,
                    'timeEnd' => $timeEnd,
                    'duration' => round($timeEnd - $timeStart, 2),
                ];
            })
            ->filter(function($data) {
                return $data['timeStart'];
            })
            ->sortBy('timeStart', SORT_NUMERIC)
            ->values();

        $start = $batch->min('timeStart');
        $end = $batch->max('timeEnd');

        $duration = round($end - $start, 2);

        $entry->content = [
            'start' => $start,
            'end' => $end,
            'duration' => $duration,
        ];

        if ( ! $duration) {
            return response()->json([
                'entry' => $entry,
                'batch' => [],
            ]);
        }

        $batch = $batch->map(function(array $data) use($start, $duration) {

            $data['left'] = ($data['timeStart'] - $start) / $duration * 100;
            $data['width'] = $data['duration'] / $duration * 100;

            return $data;
        });

        return response()->json([
            'entry' => $entry,
            'batch' => $batch,
        ]);
    }

    private function getTimesForEntry(EntryResult $entry)
    {
        $end = $entry->content['timeEnd'] * 1000;
        $start = $end;

        if ($entry->type === EntryType::QUERY) {
            $start -= $entry->content['time'] ?? 0;
        }

        if ($entry->type === EntryType::REQUEST) {
            $start -= $entry->content['duration'] ?? 0;
        }

        if ($entry->type === EntryType::TIMING) {
            $start -= $entry->content['duration'] ?? 0;
        }

        return [round($start, 2), round($end, 2)];
    }

    private function getLabelForEntry(EntryResult $entry)
    {
        if ($entry->type === EntryType::QUERY) {
            return $entry->content['sql'] ?? 'SQL Query';
        }

        if ($entry->type === EntryType::LOG) {
            return $entry->content['message'] ?? 'LOG';
        }

        if ($entry->type === EntryType::TIMING) {
            return $entry->content['label'] ?? 'TIMING';
        }

        return $entry->type;
    }
}
