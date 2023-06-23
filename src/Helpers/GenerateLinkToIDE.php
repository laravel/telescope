<?php


namespace Laravel\Telescope\Helpers;


use ReflectionMethod;

class GenerateLinkToIDE
{
    private const EDITORS = [
        'atom' => 'atom://core/open/file?filename=%path&line=%line',
        'emacs' => 'emacs://open?url=file://%path&line=%line',
        'idea' => 'idea://open?file=%path&line=%line',
        'macvim' => 'mvim://open/?url=file://%path&line=%line',
        'netbeans' => 'netbeans://open/?f=%path:%line',
        'nova' => 'nova://open?path=%path&line=%line',
        'phpstorm' => 'open?url=file://%path&line=%line',
        'sublime' => 'subl://open?url=file://%path&line=%line',
        'textmate' => 'txmt://open?url=file://%path&line=%line',
        'vscode' => 'vscode://file/%path:%line',
        'vscodium' => 'vscodium://file/%path:%line',
        'xdebug' => 'xdebug://%path@%line',
    ];

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
