<?php

namespace Laravel\Telescope\Contracts;

interface TerminableRepository
{
    /**
     * Perform any clean-up tasks needed after storing Telescope entries.
     *
     * @return void
     */
    public function terminate();
}
