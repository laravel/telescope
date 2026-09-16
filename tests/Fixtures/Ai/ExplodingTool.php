<?php

namespace Laravel\Telescope\Tests\Fixtures\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use RuntimeException;
use Stringable;

class ExplodingTool implements Tool
{
    public function name(): string
    {
        return 'explode';
    }

    public function description(): Stringable|string
    {
        return 'Always throws.';
    }

    public function handle(Request $request): Stringable|string
    {
        throw new RuntimeException('Tool exploded.');
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
