<?php

namespace Laravel\Telescope\Contracts;

use Illuminate\Support\Collection;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\Storage\EntryQueryOptions;

interface EntriesRepository
{
    /**
     * Return an entry with the given ID.
     *
     * @param  mixed  $id
     * @return \Laravel\Telescope\EntryResult
     */
    public function find($id): EntryResult;

    /**
     * Return all the entries of a given type.
     *
     * @param  string|null  $type
     * @param  \Laravel\Telescope\Storage\EntryQueryOptions  $options
     * @return \Illuminate\Support\Collection|\Laravel\Telescope\EntryResult[]
     */
    public function get($type, EntryQueryOptions $options);

    /**
     * Store the given entries.
     *
     * @param  \Illuminate\Support\Collection|\Laravel\Telescope\IncomingEntry[]  $entries
     * @return void
     */
    public function store(Collection $entries);

    /**
     * Store the given entry updates and return the failed updates.
     *
     * @param  \Illuminate\Support\Collection|\Laravel\Telescope\EntryUpdate[]  $updates
     * @return \Illuminate\Support\Collection|null
     */
    public function update(Collection $updates);

    /**
     * Load the monitored tags from storage.
     *
     * @return void
     */
    public function loadMonitoredTags();

    /**
     * Determine if any of the given tags are currently being monitored.
     *
     * @param  array  $tags
     * @return bool
     */
    public function isMonitoring(array $tags);

    /**
     * Get the list of tags currently being monitored.
     *
     * @return array
     */
    public function monitoring();

    /**
     * Begin monitoring the given list of tags.
     *
     * @param  array  $tags
     * @return void
     */
    public function monitor(array $tags);

    /**
     * Stop monitoring the given list of tags.
     *
     * @param  array  $tags
     * @return void
     */
    public function stopMonitoring(array $tags);

    /**
     * Get the list of security whitelist patterns.
     *
     * @return array
     */
    public function securityWhitelist();

    /**
     * Add a pattern to the security whitelist.
     *
     * @param  array  $pattern
     * @return int
     */
    public function addSecurityWhitelist(array $pattern);

    /**
     * Update a security whitelist pattern.
     *
     * @param  int  $id
     * @param  array  $pattern
     * @return void
     */
    public function updateSecurityWhitelist(int $id, array $pattern);

    /**
     * Remove a pattern from the security whitelist.
     *
     * @param  int  $id
     * @return void
     */
    public function removeSecurityWhitelist(int $id);

    /**
     * Get all global security rules.
     *
     * @return array
     */
    public function globalSecurityRules();

    /**
     * Add a global security rule.
     *
     * @param  array  $rule
     * @return int
     */
    public function addGlobalSecurityRule(array $rule);

    /**
     * Update a global security rule.
     *
     * @param  int  $id
     * @param  array  $rule
     * @return void
     */
    public function updateGlobalSecurityRule(int $id, array $rule);

    /**
     * Remove a global security rule.
     *
     * @param  int  $id
     * @return void
     */
    public function removeGlobalSecurityRule(int $id);

    /**
     * Initialize default global security rules.
     *
     * @return void
     */
    public function initializeDefaultGlobalSecurityRules();
}
