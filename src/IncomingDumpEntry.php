<?php

namespace Laravel\Telescope;

class IncomingDumpEntry extends IncomingEntry
{
    /**
     * Determine if the incoming entry is an exception.
     *
     * @return bool
     */
    public function isDump()
    {
        return true;
    }

    /**
     * Assign entry point parameters from the given batch entries.
     *
     * @param  array  $batch
     * @return $this
     */
    public function assignEntryPointFromBatch(array $batch)
    {
        $entryPoint = collect($batch)->first(function ($entry) {
            return in_array($entry->type, [EntryType::REQUEST, EntryType::JOB, EntryType::COMMAND]);
        });

        if (! $entryPoint) {
            return;
        }

        $this->content = array_merge($this->content, [
            'entry_point_type' => $entryPoint->type,
            'entry_point_uuid' => $entryPoint->uuid,
            'entry_point_description' => $this->entryPointDescription($entryPoint),
        ]);
    }

    /**
     * Description for the entry point.
     *
     * @param  IncomingDumpEntry  $entryPoint
     * @return string
     */
    private function entryPointDescription($entryPoint)
    {
        switch ($entryPoint->type) {
            case (EntryType::REQUEST):
                return $entryPoint->content['method'].' '.$entryPoint->content['uri'];
                break;
            case (EntryType::JOB):
                return $entryPoint->content['name'];
                break;
            case (EntryType::COMMAND):
                return $entryPoint->content['command'];
                break;
        }

        return '';
    }
}
