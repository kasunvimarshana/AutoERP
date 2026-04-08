<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

/** ValueObjectGenerator */
final class ValueObjectGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'domain/value-object'; }
    public function label(): string   { return 'Value Object'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.value_objects'),
            "{$className}.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.value_objects'));
    }
}
