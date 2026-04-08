<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class SpecificationGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'domain/specification'; }
    public function label(): string   { return 'Specification'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.specifications'),
            "{$className}.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.specifications'));
    }
}
