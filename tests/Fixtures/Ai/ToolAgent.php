<?php

namespace Laravel\Telescope\Tests\Fixtures\Ai;

use Laravel\Ai\Contracts\HasTools;

class ToolAgent extends Agent implements HasTools
{
    public function tools(): iterable
    {
        return [new RefundTool, new ExplodingTool];
    }
}
