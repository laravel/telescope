<?php

namespace Laravel\Telescope\Tests\Fixtures\Ai;

class FailoverAgent extends Agent
{
    public function provider(): array|string
    {
        return ['openai' => 'gpt-5', 'anthropic' => 'claude-sonnet-5'];
    }
}
