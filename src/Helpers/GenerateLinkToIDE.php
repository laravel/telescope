<?php


namespace Laravel\Telescope\Helpers;


use ReflectionMethod;

class GenerateLinkToIDE
{
    public static function make(?string $action = null): ?string
    {
        $parts = array_filter(explode('@', $action));

        if (count($parts) == 2) {
            try {
                $method = new ReflectionMethod(...$parts);

                return sprintf('phpstorm://open?url=file://%s&line=%d', $method->getFileName(), $method->getStartLine());
            } catch (\Throwable $e) {
            }
        }

        return null;
    }
}
