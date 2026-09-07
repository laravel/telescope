<?php

namespace Laravel\Telescope\Console\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\EntryType;

trait FormatsOutput
{
    /**
     * Get a shortened UUID for display.
     *
     * @param  string  $uuid
     * @return string
     */
    protected function shortUuid(string $uuid): string
    {
        return substr($uuid, 0, 8);
    }

    /**
     * Format a timestamp as a human readable difference.
     *
     * @param  mixed  $date
     * @return string
     */
    protected function humanTime($date): string
    {
        return Carbon::parse($date)->diffForHumans();
    }

    /**
     * Generate a one-line summary for the given entry.
     *
     * @param  \Laravel\Telescope\EntryResult  $entry
     * @return string
     */
    protected function summarizeEntry(EntryResult $entry): string
    {
        $content = $entry->content;

        return match ($entry->type) {
            EntryType::REQUEST => ($content['method'] ?? '').' '.($content['uri'] ?? '').' -> '.($content['response_status'] ?? '').' ('.($content['duration'] ?? '').'ms)',
            EntryType::EXCEPTION => Str::limit(($content['class'] ?? '').': '.($content['message'] ?? ''), 80),
            EntryType::QUERY => Str::limit($content['sql'] ?? '', 60).' ('.($content['time'] ?? '').'ms)',
            EntryType::JOB => class_basename($content['name'] ?? '').' ['.($content['status'] ?? '').']',
            EntryType::CACHE => ($content['type'] ?? '').' '.($content['key'] ?? ''),
            EntryType::LOG => '['.($content['level'] ?? '').'] '.Str::limit($content['message'] ?? '', 60),
            EntryType::MAIL => Str::limit($content['subject'] ?? $content['mailable'] ?? '', 80),
            EntryType::EVENT => ($content['name'] ?? '').(isset($content['listeners']) ? ' ('.count($content['listeners']).' listeners)' : ''),
            EntryType::MODEL => ($content['model'] ?? '').': '.($content['action'] ?? ''),
            EntryType::GATE => ($content['ability'] ?? '').': '.($content['result'] ?? ''),
            EntryType::VIEW => $content['name'] ?? '',
            EntryType::NOTIFICATION => class_basename($content['notification'] ?? '').' via '.($content['channel'] ?? ''),
            EntryType::REDIS => Str::limit($content['command'] ?? '', 60),
            EntryType::CLIENT_REQUEST => ($content['method'] ?? '').' '.Str::limit($content['uri'] ?? '', 40).' -> '.($content['response_status'] ?? 'N/A'),
            EntryType::COMMAND => $content['command'] ?? '',
            EntryType::SCHEDULED_TASK => ($content['command'] ?? '').' ['.($content['expression'] ?? '').']',
            default => Str::limit(json_encode($content), 80),
        };
    }

    /**
     * Colorize an HTTP method for console output.
     *
     * @param  string  $method
     * @return string
     */
    protected function colorMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => "<fg=gray>{$method}</>",
            'POST', 'PATCH', 'PUT' => "<fg=blue>{$method}</>",
            'DELETE' => "<fg=red>{$method}</>",
            default => $method,
        };
    }

    /**
     * Colorize an HTTP status code for console output.
     *
     * @param  int  $status
     * @return string
     */
    protected function colorStatus(int $status): string
    {
        return match (true) {
            $status < 300 => "<fg=green>{$status}</>",
            $status < 400 => "<fg=blue>{$status}</>",
            $status < 500 => "<fg=yellow>{$status}</>",
            default => "<fg=red>{$status}</>",
        };
    }

    /**
     * Colorize a log level for console output.
     *
     * @param  string  $level
     * @return string
     */
    protected function colorLevel(string $level): string
    {
        return match ($level) {
            'emergency', 'alert', 'critical', 'error' => "<fg=red>{$level}</>",
            'warning' => "<fg=yellow>{$level}</>",
            'notice', 'info' => "<fg=blue>{$level}</>",
            'debug' => "<fg=gray>{$level}</>",
            default => $level,
        };
    }

    /**
     * Colorize a cache action for console output.
     *
     * @param  string  $type
     * @return string
     */
    protected function colorCacheAction(string $type): string
    {
        return match ($type) {
            'hit' => '<fg=green>HIT</>',
            'missed' => '<fg=red>MISS</>',
            'set' => '<fg=blue>SET</>',
            'forget' => '<fg=yellow>FORGET</>',
            default => $type,
        };
    }

    /**
     * Colorize a job status for console output.
     *
     * @param  string  $status
     * @return string
     */
    protected function colorJobStatus(string $status): string
    {
        return match ($status) {
            'processed' => "<fg=green>{$status}</>",
            'failed' => "<fg=red>{$status}</>",
            'pending' => "<fg=yellow>{$status}</>",
            default => $status,
        };
    }

    /**
     * Format a value as a pretty-printed JSON block.
     *
     * @param  mixed  $data
     * @param  int|null  $limit
     * @return string
     */
    protected function jsonBlock($data, ?int $limit = null): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $limit ? Str::limit($json, $limit) : $json;
    }
}
