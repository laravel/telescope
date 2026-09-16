<?php

namespace Laravel\Telescope\Tests\Fixtures\Ai;

use Laravel\Ai\Contracts\Agent as AgentContract;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

class Agent implements AgentContract, Conversational
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You are a helpful assistant.';
    }

    public function messages(): iterable
    {
        return [];
    }

    public function provider(): array|string
    {
        return 'openai';
    }

    public function model(): string
    {
        return 'gpt-5';
    }
}
