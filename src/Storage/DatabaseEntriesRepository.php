<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;

class DatabaseEntriesRepository implements Contract
{
    /**
     * Find the entry with the given ID.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function find($id)
    {
        $entry = DB::table('telescope_entries')
                    ->whereId($id)
                    ->first();

        abort_unless($entry, 404);

        return tap($entry, function ($entry) {
            $entry->content = json_decode($entry->content);
        });
    }

    /**
     * Return all the entries of a given type.
     *
     * @param  int  $type
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function get($type, $options = [])
    {
        return DB::table('telescope_entries')
            ->when($type, function ($q, $value) {
                return $q->where('type', $value);
            })
            ->when($options['before'] ?? false, function ($q, $value) {
                return $q->where('id', '<', $value);
            })
            ->when($options['tag'] ?? false, function ($q, $value) {
                $records = DB::table('telescope_entries_tags')->whereTag($value)->pluck('entry_id')->toArray();

                return $q->whereIn('id', $records);
            })
            ->when($options['batch_id'] ?? false, function ($q, $value) {
                return $q->where('batch_id', $value);
            })
            ->take($options['take'] ?? 50)
            ->orderByDesc('id')
            ->get()
            ->each(function ($entry) {
                $entry->content = json_decode($entry->content);
            });
    }

    /**
     * Store the given array of entries.
     *
     * @param  string  $batchId
     * @param  array  $data
     * @return mixed
     */
    public function store($batchId, $data)
    {
        collect($data)->each(function ($entry) use ($batchId) {
            $entry['batch_id'] = $batchId;
            $entry['content'] = json_encode($entry['content']);

            $tags = $entry['tags'];

            unset($entry['tags']);

            $id = DB::table('telescope_entries')->insertGetId($entry);

            DB::table('telescope_entries_tags')->insert(collect($tags)->map(function ($tag) use ($id) {
                return ['entry_id' => $id, 'tag' => $tag,];
            })->toArray());
        });
    }
}
