<?php

namespace Laravel\Telescope\Tests\Console;

use Laravel\Telescope\Database\Factories\EntryModelFactory;
use Laravel\Telescope\EntryType;

trait CreatesTelescopeEntries
{
    /**
     * Create an entry of the given type.
     *
     * @param  string  $type
     * @param  array  $content
     * @param  array  $attributes
     * @return \Laravel\Telescope\Storage\EntryModel
     */
    protected function createEntry(string $type, array $content = [], array $attributes = [])
    {
        return EntryModelFactory::new()->create($attributes + [
            'type' => $type,
            'content' => $content + ['hostname' => 'localhost'],
        ]);
    }

    /**
     * Create a request entry.
     *
     * @param  array  $content
     * @param  array  $attributes
     * @return \Laravel\Telescope\Storage\EntryModel
     */
    protected function createRequest(array $content = [], array $attributes = [])
    {
        return $this->createEntry(EntryType::REQUEST, $content + [
            'method' => 'GET', 'uri' => '/test', 'response_status' => 200,
            'duration' => 50, 'memory' => 8, 'ip_address' => '127.0.0.1',
            'middleware' => [], 'payload' => [], 'response' => [],
        ], $attributes);
    }

    /**
     * Create a query entry.
     *
     * @param  array  $content
     * @param  array  $attributes
     * @return \Laravel\Telescope\Storage\EntryModel
     */
    protected function createQuery(array $content = [], array $attributes = [])
    {
        return $this->createEntry(EntryType::QUERY, $content + [
            'sql' => 'select 1', 'time' => 1.0, 'connection' => 'testbench', 'slow' => false, 'hash' => 'h1',
        ], $attributes);
    }

    /**
     * Create an exception entry.
     *
     * @param  array  $content
     * @param  array  $attributes
     * @return \Laravel\Telescope\Storage\EntryModel
     */
    protected function createException(array $content = [], array $attributes = [])
    {
        return $this->createEntry(EntryType::EXCEPTION, $content + [
            'class' => 'RuntimeException', 'message' => 'Test', 'file' => 'test.php',
            'line' => 1, 'trace' => [], 'occurrences' => 1,
        ], $attributes);
    }
}
