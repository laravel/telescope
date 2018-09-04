<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Facades\DB;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;

class DatabaseEntriesRepository implements Contract
{
    /**
     * Return all the entries of a given type.
     *
     * @param  int $type
     * @param  array $params
     * @return \Illuminate\Support\Collection
     */
    public function get($type, $params = [])
    {
        return DB::table('telescope_entries')
            ->whereType($type)
            ->orderByDesc('id')
            ->take($params['take'] ?? 50)
            ->when($params['before'] ?? false, function($q, $value){
                return $q->where('id', '<', $value);
            })
            ->when($params['tag'] ?? false, function($q, $value){
                $records = DB::table('telescope_entries_tags')->whereTag($value)->pluck('entry_id')->toArray();

                return $q->whereIn('id', $records);
            })
            ->get()
            ->map(function ($entry) {
                $entry->content = json_decode($entry->content);

                return $entry;
            });
    }

    /**
     * Return an entry with the given ID.
     *
     * @param  mixed $id
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
     * Store the given entries.
     *
     * @param  array $data
     * @return mixed
     */
    public function store($data)
    {
        collect($data)->each(function ($entry) {
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
