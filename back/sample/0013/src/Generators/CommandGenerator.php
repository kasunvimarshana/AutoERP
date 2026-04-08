<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class CommandGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'application/command'; }
    public function label(): string   { return 'CQRS Command'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.commands'),
            "{$className}Command.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.commands'));
    }
}
