<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class DomainEventGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'domain/event'; }
    public function label(): string   { return 'Domain Event'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.events'),
            "{$className}.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.events'));
    }
}
