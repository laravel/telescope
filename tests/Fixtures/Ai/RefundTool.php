<?php

namespace Laravel\Telescope\Tests\Fixtures\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RefundTool implements Tool
{
    public function name(): string
    {
        return 'refund-order';
    }

    public function description(): Stringable|string
    {
        return 'Refund an order.';
    }

    public function handle(Request $request): Stringable|string
    {
        return 'Refunded order '.$request['order'].'.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['order' => $schema->integer(), 'password' => $schema->string()];
    }
}
