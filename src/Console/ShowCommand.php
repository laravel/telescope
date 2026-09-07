<?php

namespace Laravel\Telescope\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Telescope\Console\Concerns\FormatsOutput;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Laravel\Telescope\Telescope;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'telescope:show')]
class ShowCommand extends Command
{
    use FormatsOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:show
        {id : Entry UUID, "latest", or "latest:{type}" (e.g. latest:exception)}
        {--type= : Filter batch entries to specific type(s), comma-separated}
        {--full : Do not truncate SQL, messages, or payloads}
        {--json : Output the entry and its batch as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show a Telescope entry with full batch context';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @return int|void
     */
    public function handle(EntriesRepository $storage)
    {
        return Telescope::withoutRecording(function () use ($storage) {
            $entry = $this->findEntry($storage, $this->argument('id'));

            if (! $entry) {
                return 1;
            }

            $batchId = $entry->content['updated_batch_id'] ?? $entry->batchId;

            $types = Str::of($this->option('type'))->explode(',')->map(fn ($type) => trim($type))->filter();

            if (! $this->ensureValidEntryTypes(...$types)) {
                return 1;
            }

            $batchEntries = $batchId
                ? collect($storage->get(null, EntryQueryOptions::forBatchId($batchId)->limit(-1)))->reverse()->values()
                : collect();

            $batchEntries = $batchEntries->reject(fn ($other) => $other->id === $entry->id)
                ->when($types->isNotEmpty(), fn ($entries) => $entries->whereIn('type', $types))
                ->values();

            if ($this->option('json')) {
                $this->line($this->jsonBlock(['entry' => $entry, 'batch' => $batchEntries->all()]));

                return;
            }

            $this->renderEntry($entry);
            $this->renderBatchContext($batchId, $batchEntries->groupBy('type'), $types);
        });
    }

    /**
     * Find the entry with the given ID, supporting the "latest" and "latest:{type}" shortcuts.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @param  string  $id
     * @return \Laravel\Telescope\EntryResult|null
     */
    protected function findEntry(EntriesRepository $storage, string $id): ?EntryResult
    {
        if ($id === 'latest' || str_starts_with($id, 'latest:')) {
            $type = $id === 'latest' ? null : Str::after($id, 'latest:');

            if ($type && ! $this->ensureValidEntryTypes($type)) {
                return null;
            }

            $entry = collect($storage->get($type, (new EntryQueryOptions)->limit(1)))->first();

            if (! $entry) {
                $this->error('No '.($type ? "{$type} " : '').'entries found.');
            }

            return $entry;
        }

        try {
            return $storage->find($id);
        } catch (ModelNotFoundException) {
            $this->error("Entry not found: {$id}");

            return null;
        }
    }

    /**
     * Render the full detail view for the given entry.
     *
     * @param  \Laravel\Telescope\EntryResult  $entry
     * @return void
     */
    protected function renderEntry(EntryResult $entry): void
    {
        match ($entry->type) {
            EntryType::REQUEST => $this->renderRequest($entry),
            EntryType::EXCEPTION => $this->renderException($entry),
            EntryType::JOB => $this->renderJob($entry),
            default => $this->renderGenericEntry($entry),
        };
    }

    /**
     * Render a request entry.
     *
     * @param  \Laravel\Telescope\EntryResult  $entry
     * @return void
     */
    protected function renderRequest(EntryResult $entry): void
    {
        $content = $entry->content;

        $this->info('Request: '.($content['method'] ?? '').' '.($content['uri'] ?? '').' -> '.($content['response_status'] ?? ''));

        $this->details($entry, [
            'Controller' => $content['controller_action'] ?? 'Closure',
            'Duration' => $this->unit($content['duration'] ?? null, 'ms'),
            'Memory' => $this->unit($content['memory'] ?? null, 'MB'),
            'IP' => $content['ip_address'] ?? '',
            'Middleware' => implode(', ', (array) ($content['middleware'] ?? [])),
            'User' => $this->formatUser($content['user'] ?? []),
        ]);

        $this->block('Payload', $content['payload'] ?? null);
        $this->block('Response', $content['response'] ?? null, 1000);
    }

    /**
     * Render an exception entry with code context and stack trace.
     *
     * @param  \Laravel\Telescope\EntryResult  $entry
     * @return void
     */
    protected function renderException(EntryResult $entry): void
    {
        $content = $entry->content;

        $this->info('Exception: '.class_basename($content['class'] ?? ''));

        $this->details($entry, [
            'Class' => $content['class'] ?? '',
            'File' => ($content['file'] ?? '').':'.($content['line'] ?? ''),
            'Occurrences' => (string) ($content['occurrences'] ?? 1),
            'Resolved' => $content['resolved_at'] ?? '<fg=yellow>No</>',
        ]);

        $this->error($content['message'] ?? '');

        if (! empty($content['line_preview'])) {
            $this->info('Code Context');

            $this->table([], collect($content['line_preview'])->map(fn ($code, $lineNo) => [
                $lineNo == ($content['line'] ?? 0) ? "<fg=red>{$lineNo} ></>" : $lineNo,
                $code,
            ])->values());
        }

        $this->renderTrace($content['trace'] ?? []);
    }

    /**
     * Render a job entry with exception detail if failed.
     *
     * @param  \Laravel\Telescope\EntryResult  $entry
     * @return void
     */
    protected function renderJob(EntryResult $entry): void
    {
        $content = $entry->content;

        $this->info('Job: '.class_basename($content['name'] ?? '').' ['.($content['status'] ?? 'pending').']');

        $this->details($entry, [
            'Status' => $this->colorJobStatus($content['status'] ?? 'pending'),
            'Queue' => $content['queue'] ?? '',
            'Connection' => $content['connection'] ?? '',
            'Tries' => (string) ($content['tries'] ?? ''),
            'Timeout' => $this->unit($content['timeout'] ?? null, 's'),
        ]);

        $this->block('Data', $content['data'] ?? null, 500);

        if (! empty($content['exception'])) {
            $this->error($content['exception']['message'] ?? '');
            $this->renderTrace($content['exception']['trace'] ?? [], 10);
        }
    }

    /**
     * Render any entry type using a data-driven field map.
     *
     * @param  \Laravel\Telescope\EntryResult  $entry
     * @return void
     */
    protected function renderGenericEntry(EntryResult $entry): void
    {
        $content = $entry->content;
        $config = $this->entryFieldConfig($entry->type, $content) + [
            'label' => ucfirst($entry->type), 'subtitle' => '', 'fields' => [], 'list' => null, 'blocks' => [],
        ];

        $this->info($config['subtitle'] === ''
            ? $config['label']
            : "{$config['label']}: {$config['subtitle']}");

        $this->details($entry, $config['fields']);

        if (! empty($config['list'])) {
            $this->listing($config['list']['label'], $config['list']['items']);
        }

        foreach ($config['blocks'] as $label => $key) {
            $this->block($label, $key === null ? $content : ($content[$key] ?? null), $key === null ? 1000 : 500);
        }
    }

    /**
     * Get the field configuration for a given entry type.
     *
     * @param  string  $type
     * @param  array  $content
     * @return array
     */
    protected function entryFieldConfig(string $type, array $content): array
    {
        return match ($type) {
            EntryType::QUERY => [
                'label' => 'Query',
                'fields' => [
                    'Connection' => $content['connection'] ?? '',
                    'Duration' => $this->unit($content['time'] ?? null, 'ms').(! empty($content['slow']) ? '  <fg=red>SLOW</>' : ''),
                    'Source' => isset($content['file']) ? ($content['file']).':'.($content['line'] ?? '') : '',
                    'SQL' => $content['sql'] ?? '',
                ],
                'blocks' => ['Bindings' => 'bindings'],
            ],
            EntryType::CACHE => [
                'label' => 'Cache', 'subtitle' => $content['type'] ?? '',
                'fields' => [
                    'Key' => $content['key'] ?? '',
                    'Expiration' => $this->unit($content['expiration'] ?? null, 's'),
                ],
                'blocks' => ['Value' => 'value'],
            ],
            EntryType::LOG => [
                'label' => 'Log', 'subtitle' => $content['level'] ?? '',
                'fields' => ['Message' => $content['message'] ?? ''],
                'blocks' => ['Context' => 'context'],
            ],
            EntryType::MAIL => [
                'label' => 'Mail', 'subtitle' => $content['subject'] ?? '',
                'fields' => [
                    'Mailable' => $content['mailable'] ?? '', 'Subject' => $content['subject'] ?? '',
                    'Queued' => ! empty($content['queued']) ? 'Yes' : '',
                    'To' => $this->formatAddresses($content['to'] ?? []),
                    'From' => $this->formatAddresses($content['from'] ?? []),
                ],
            ],
            EntryType::EVENT => [
                'label' => 'Event', 'subtitle' => $content['name'] ?? '',
                'fields' => ['Broadcast' => ! empty($content['broadcast']) ? 'Yes' : ''],
                'list' => ! empty($content['listeners']) ? ['label' => 'Listeners', 'items' => collect($content['listeners'])->map(fn ($listener) => $listener['name'].(empty($listener['queued']) ? '' : ' (queued)'))->all()] : null,
                'blocks' => ['Payload' => 'payload'],
            ],
            EntryType::COMMAND => [
                'label' => 'Command', 'subtitle' => $content['command'] ?? '',
                'fields' => ['Exit Code' => (string) ($content['exit_code'] ?? '')],
                'blocks' => ['Arguments' => 'arguments', 'Options' => 'options'],
            ],
            EntryType::SCHEDULED_TASK => [
                'label' => 'Scheduled Task', 'subtitle' => $content['command'] ?? '',
                'fields' => [
                    'Expression' => $content['expression'] ?? '', 'Timezone' => $content['timezone'] ?? '',
                    'Description' => $content['description'] ?? '',
                ],
                'blocks' => ['Output' => 'output'],
            ],
            EntryType::CLIENT_REQUEST => [
                'label' => 'Client Request', 'subtitle' => ($content['method'] ?? '').' '.($content['uri'] ?? ''),
                'fields' => [
                    'Status' => isset($content['response_status']) ? $this->colorStatus((int) $content['response_status']) : 'N/A',
                    'Duration' => $this->unit($content['duration'] ?? null, 'ms'),
                ],
                'blocks' => ['Payload' => 'payload', 'Response' => 'response'],
            ],
            EntryType::GATE => [
                'label' => 'Gate',
                'subtitle' => ($content['ability'] ?? '').': '.($content['result'] ?? ''),
                'fields' => ['Source' => isset($content['file']) ? $content['file'].':'.($content['line'] ?? '') : ''],
                'blocks' => ['Arguments' => 'arguments'],
            ],
            EntryType::MODEL => [
                'label' => 'Model', 'subtitle' => ($content['model'] ?? '').': '.($content['action'] ?? ''),
                'fields' => ['Count' => isset($content['count']) ? (string) $content['count'] : ''],
                'blocks' => ['Changes' => 'changes'],
            ],
            EntryType::NOTIFICATION => [
                'label' => 'Notification', 'subtitle' => class_basename($content['notification'] ?? ''),
                'fields' => [
                    'Channel' => $content['channel'] ?? '', 'Notifiable' => $content['notifiable'] ?? '',
                    'Queued' => ! empty($content['queued']) ? 'Yes' : '',
                ],
            ],
            EntryType::VIEW => [
                'label' => 'View', 'subtitle' => $content['name'] ?? '',
                'fields' => ['Path' => $content['path'] ?? ''],
                'list' => ! empty($content['composers']) ? [
                    'label' => 'Composers',
                    'items' => collect($content['composers'])->map(fn ($composer) => ($composer['name'] ?? '').(isset($composer['type']) ? " ({$composer['type']})" : ''))->all(),
                ] : null,
                'blocks' => ['Data' => 'data'],
            ],
            EntryType::REDIS => [
                'label' => 'Redis',
                'fields' => [
                    'Connection' => $content['connection'] ?? '',
                    'Duration' => $this->unit($content['time'] ?? null, 'ms'),
                    'Command' => $content['command'] ?? '',
                ],
            ],
            default => [
                'label' => ucfirst($type),
                'blocks' => ['Content' => null],
            ],
        };
    }

    /**
     * Render the batch context for the given entry.
     *
     * @param  string|null  $batchId
     * @param  \Illuminate\Support\Collection  $batchByType
     * @param  \Illuminate\Support\Collection  $requestedTypes
     * @return void
     */
    protected function renderBatchContext(?string $batchId, Collection $batchByType, Collection $requestedTypes): void
    {
        if ($batchByType->isEmpty()) {
            if ($requestedTypes->isNotEmpty()) {
                $this->line('No batch entries of type '.$requestedTypes->implode(', ').'.');
            }

            return;
        }

        $this->info('Related Entries - batch '.$this->shortUuid($batchId));

        $detailed = [EntryType::QUERY, EntryType::EXCEPTION, EntryType::CACHE, EntryType::LOG];

        $this->renderBatchQueries($batchByType->get(EntryType::QUERY, collect()));
        $this->renderBatchExceptions($batchByType->get(EntryType::EXCEPTION, collect()));
        $this->renderBatchCache($batchByType->get(EntryType::CACHE, collect()));
        $this->renderBatchLogs($batchByType->get(EntryType::LOG, collect()));

        foreach ($batchByType->except($detailed) as $type => $entries) {
            $label = Str::plural(Str::headline($type));

            $this->listing("{$label} ({$entries->count()})", $entries->take(5)->map(fn ($related) => $this->summarizeEntry($related))->all());
            $this->more($entries->count(), 5, $label);
        }
    }

    /**
     * Render the batch query section with stats.
     *
     * @param  \Illuminate\Support\Collection  $queries
     * @return void
     */
    protected function renderBatchQueries(Collection $queries): void
    {
        if ($queries->isEmpty()) {
            return;
        }

        $totalTime = round($queries->sum(fn ($query) => (float) ($query->content['time'] ?? 0)), 2);
        $slowCount = $queries->filter(fn ($query) => ! empty($query->content['slow']))->count();

        $duplicates = $queries->groupBy(fn ($query) => $query->content['hash'] ?? $query->content['sql'] ?? '')
            ->filter(fn ($group) => $group->count() > 1);

        $this->info(
            "Queries - {$queries->count()} total, {$totalTime}ms".
            ($slowCount > 0 ? ", {$slowCount} slow" : '').
            ($duplicates->isNotEmpty() ? ', '.$duplicates->count().' duplicate '.Str::plural('group', $duplicates->count()) : '')
        );

        $this->table(
            ['#', 'UUID', 'Time', 'SQL', 'Source', 'Flags'],
            $queries->take(20)->values()->map(fn ($query, $index) => [
                $index + 1,
                $this->shortUuid($query->id),
                round((float) ($query->content['time'] ?? 0), 2).'ms',
                $this->limit($query->content['sql'] ?? '', 60),
                isset($query->content['file']) ? basename($query->content['file']).':'.($query->content['line'] ?? '') : '',
                trim(
                    (! empty($query->content['slow']) ? '<fg=red>SLOW</> ' : '').
                    ($duplicates->has($query->content['hash'] ?? $query->content['sql'] ?? '') ? '<fg=yellow>DUP</>' : '')
                ),
            ])
        );

        $this->more($queries->count(), 20, 'queries');
    }

    /**
     * Render the batch exception section.
     *
     * @param  \Illuminate\Support\Collection  $exceptions
     * @return void
     */
    protected function renderBatchExceptions(Collection $exceptions): void
    {
        if ($exceptions->isEmpty()) {
            return;
        }

        $this->info("Exceptions - {$exceptions->count()}");

        $this->table(
            ['UUID', 'Exception', 'Location'],
            $exceptions->take(10)->map(fn ($exception) => [
                $this->shortUuid($exception->id),
                ($exception->content['class'] ?? '').': '.$this->limit($exception->content['message'] ?? '', 80),
                ($exception->content['file'] ?? '').':'.($exception->content['line'] ?? ''),
            ])
        );

        $this->more($exceptions->count(), 10, 'exceptions');
    }

    /**
     * Render the batch cache section with hit rate.
     *
     * @param  \Illuminate\Support\Collection  $cacheEntries
     * @return void
     */
    protected function renderBatchCache(Collection $cacheEntries): void
    {
        if ($cacheEntries->isEmpty()) {
            return;
        }

        $hits = $cacheEntries->filter(fn ($cacheEntry) => ($cacheEntry->content['type'] ?? '') === 'hit')->count();
        $misses = $cacheEntries->filter(fn ($cacheEntry) => ($cacheEntry->content['type'] ?? '') === 'missed')->count();
        $lookups = $hits + $misses;

        $this->info("Cache - {$hits} hits, {$misses} misses".
            ($lookups > 0 ? ' - '.round($hits / $lookups * 100, 1).'% hit rate' : ''));

        $this->table(
            ['Action', 'Key'],
            $cacheEntries->take(10)->map(fn ($cacheEntry) => [
                $this->colorCacheAction($cacheEntry->content['type'] ?? ''),
                $this->limit($cacheEntry->content['key'] ?? '', 60),
            ])
        );

        $this->more($cacheEntries->count(), 10, 'cache entries');
    }

    /**
     * Render the batch log section.
     *
     * @param  \Illuminate\Support\Collection  $logs
     * @return void
     */
    protected function renderBatchLogs(Collection $logs): void
    {
        if ($logs->isEmpty()) {
            return;
        }

        $this->info("Logs - {$logs->count()}");

        $this->table(
            ['Level', 'Message'],
            $logs->take(10)->map(fn ($log) => [
                $this->colorLevel($log->content['level'] ?? ''),
                $this->limit($log->content['message'] ?? '', 80),
            ])
        );

        $this->more($logs->count(), 10, 'logs');
    }

    /**
     * Render the entry's common fields followed by the given fields as a key/value table.
     *
     * @param  \Laravel\Telescope\EntryResult  $entry
     * @param  array<string, string|null>  $fields
     * @return void
     */
    protected function details(EntryResult $entry, array $fields): void
    {
        $rows = collect([
            'Time' => $this->humanTime($entry->createdAt)."  <fg=gray>({$entry->createdAt})</>",
            'Hostname' => $entry->content['hostname'] ?? '',
        ] + $fields)
            ->filter(fn ($value) => $value !== '' && $value !== null)
            ->map(fn ($value, $label) => [$label, (string) $value])
            ->values();

        $this->table([], $rows);
    }

    /**
     * Render a titled single-column list.
     *
     * @param  string  $title
     * @param  array  $items
     * @return void
     */
    protected function listing(string $title, array $items): void
    {
        $this->info($title);

        $this->table([], collect($items)->map(fn ($item) => [$item]));
    }

    /**
     * Render a titled content block (JSON or plain text) if it has a value.
     *
     * @param  string  $label
     * @param  mixed  $value
     * @param  int  $limit
     * @return void
     */
    protected function block(string $label, $value, int $limit = 500): void
    {
        if (empty($value)) {
            return;
        }

        $this->info($label);

        $this->line($this->limit(is_string($value) ? $value : $this->jsonBlock($value), $limit));
    }

    /**
     * Truncate the given value unless the --full option was given.
     *
     * @param  string  $value
     * @param  int  $limit
     * @return string
     */
    protected function limit(string $value, int $limit): string
    {
        return $this->option('full') ? $value : Str::limit($value, $limit);
    }

    /**
     * Note how many items were omitted beyond the given limit.
     *
     * @param  int  $count
     * @param  int  $limit
     * @param  string  $label
     * @return void
     */
    protected function more(int $count, int $limit, string $label): void
    {
        if ($count > $limit) {
            $this->line('... and '.($count - $limit)." more {$label}");
        }
    }

    /**
     * Render a stack trace.
     *
     * @param  array  $trace
     * @param  int  $limit
     * @return void
     */
    protected function renderTrace(array $trace, int $limit = 15): void
    {
        if (empty($trace)) {
            return;
        }

        $this->listing('Stack Trace', collect($trace)->take($limit)->map(fn ($frame) => ($frame['file'] ?? '?').':'.($frame['line'] ?? '?'))->all());

        $this->more(count($trace), $limit, 'frames');
    }

    /**
     * Format the authenticated user for display.
     *
     * @param  array  $user
     * @return string
     */
    protected function formatUser(array $user): string
    {
        return implode(' ', array_filter([
            $user['name'] ?? null,
            isset($user['email']) ? "({$user['email']})" : null,
            isset($user['id']) ? "#{$user['id']}" : null,
        ]));
    }

    /**
     * Format an array of email addresses for display.
     *
     * @param  array  $addresses
     * @return string
     */
    protected function formatAddresses(array $addresses): string
    {
        return collect($addresses)->map(fn ($name, $email) => $name ? "{$name} <{$email}>" : $email)->implode(', ');
    }
}
