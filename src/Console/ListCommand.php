<?php

namespace Laravel\Telescope\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Telescope\Console\Concerns\FormatsOutput;
use Laravel\Telescope\Contracts\EntriesRepository;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Laravel\Telescope\Telescope;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'telescope:list')]
class ListCommand extends Command
{
    use FormatsOutput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telescope:list
        {type? : Entry type (request, query, exception, job, cache, mail, log, event, gate, model, notification, redis, view, command, schedule, client_request, batch, dump)}
        {--tag= : Filter by tag}
        {--batch= : Filter by batch ID}
        {--family= : Filter by family hash}
        {--limit=20 : Max entries to show}
        {--before= : Pagination cursor (sequence ID)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List Telescope entries';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Telescope\Contracts\EntriesRepository  $storage
     * @return int|void
     */
    public function handle(EntriesRepository $storage)
    {
        return Telescope::withoutRecording(function () use ($storage) {
            $type = $this->argument('type');

            $types = (new ReflectionClass(EntryType::class))->getConstants();

            if ($type && ! in_array($type, $types)) {
                $this->error("Invalid entry type: {$type}");
                $this->line('Valid types: '.implode(', ', $types));

                return 1;
            }

            $limit = (int) $this->option('limit');

            $entries = $storage->get($type, $this->queryOptions($limit));

            if ($entries->isEmpty()) {
                $this->warn('No entries found.');

                return;
            }

            $this->renderTable($type, $entries);

            $nextPage = $limit > 0 && $entries->count() >= $limit
                ? "Use --before={$entries->last()->sequence} for next page"
                : 'No more entries';

            $this->info("Showing {$entries->count()} entries — {$nextPage}");
        });
    }

    /**
     * Build the entry query options from the command's options.
     *
     * @param  int  $limit
     * @return \Laravel\Telescope\Storage\EntryQueryOptions
     */
    protected function queryOptions(int $limit): EntryQueryOptions
    {
        return (new EntryQueryOptions)
            ->tag($this->option('tag'))
            ->batchId($this->option('batch'))
            ->familyHash($this->option('family'))
            ->beforeSequence($this->option('before'))
            ->limit($limit);
    }

    /**
     * Render the entries table for the given entry type.
     *
     * @param  string|null  $type
     * @param  \Illuminate\Support\Collection  $entries
     * @return void
     */
    protected function renderTable(?string $type, Collection $entries): void
    {
        [$headers, $row] = $this->tableColumns($type);

        $this->table($headers, $entries->map($row)->all());
    }

    /**
     * Get the table headers and row formatter for the given entry type.
     *
     * @param  string|null  $type
     * @return array{0: string[], 1: \Closure}
     */
    protected function tableColumns(?string $type): array
    {
        return match ($type) {
            EntryType::REQUEST => [
                ['UUID', 'Method', 'URI', 'Status', 'Duration', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    $this->colorMethod($entry->content['method'] ?? ''),
                    Str::limit($entry->content['uri'] ?? '', 40),
                    $this->colorStatus((int) ($entry->content['response_status'] ?? 0)),
                    ($entry->content['duration'] ?? '').'ms',
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::QUERY => [
                ['UUID', 'SQL', 'Time', 'Slow', 'Connection', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    Str::limit($entry->content['sql'] ?? '', 60),
                    ($entry->content['time'] ?? '').'ms',
                    ! empty($entry->content['slow']) ? '<fg=red>Yes</>' : 'No',
                    $entry->content['connection'] ?? '',
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::EXCEPTION => [
                ['UUID', 'Class', 'Message', 'Occurrences', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    class_basename($entry->content['class'] ?? ''),
                    Str::limit($entry->content['message'] ?? '', 50),
                    $entry->content['occurrences'] ?? 1,
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::JOB => [
                ['UUID', 'Name', 'Queue', 'Status', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    class_basename($entry->content['name'] ?? ''),
                    $entry->content['queue'] ?? '',
                    $this->colorJobStatus($entry->content['status'] ?? ''),
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::CACHE => [
                ['UUID', 'Action', 'Key', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    $this->colorCacheAction($entry->content['type'] ?? ''),
                    Str::limit($entry->content['key'] ?? '', 50),
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::LOG => [
                ['UUID', 'Level', 'Message', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    $this->colorLevel($entry->content['level'] ?? ''),
                    Str::limit($entry->content['message'] ?? '', 60),
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::MAIL => [
                ['UUID', 'Mailable', 'Subject', 'To', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    class_basename($entry->content['mailable'] ?? ''),
                    Str::limit($entry->content['subject'] ?? '', 40),
                    Str::limit(implode(', ', array_keys($entry->content['to'] ?? [])), 30),
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::COMMAND => [
                ['UUID', 'Command', 'Exit Code', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    Str::limit($entry->content['command'] ?? '', 50),
                    $entry->content['exit_code'] ?? '',
                    $this->humanTime($entry->createdAt),
                ],
            ],
            EntryType::CLIENT_REQUEST => [
                ['UUID', 'Method', 'URI', 'Status', 'Duration', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    $this->colorMethod($entry->content['method'] ?? ''),
                    Str::limit($entry->content['uri'] ?? '', 40),
                    isset($entry->content['response_status']) ? $this->colorStatus((int) $entry->content['response_status']) : 'N/A',
                    ($entry->content['duration'] ?? '').'ms',
                    $this->humanTime($entry->createdAt),
                ],
            ],
            default => [
                ['UUID', 'Type', 'Summary', 'Created'],
                fn ($entry) => [
                    $this->shortUuid($entry->id),
                    $entry->type,
                    $this->summarizeEntry($entry),
                    $this->humanTime($entry->createdAt),
                ],
            ],
        };
    }
}
