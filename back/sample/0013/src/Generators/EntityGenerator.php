<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

/**
 * EntityGenerator — Generates a Domain Entity class.
 *
 * Generated path:  {contextBase}/{Domain}/{Entities}/{ClassName}.php
 * Generated class: {contextNamespace}\{Domain}\{Entities}\{ClassName}
 */
final class EntityGenerator extends AbstractGenerator
{
    public function stubKey(): string
    {
        return 'domain/entity';
    }

    public function label(): string
    {
        return 'Domain Entity';
    }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath(
            $context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.entities'),
            "{$className}.php"
        );
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace(
            $context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.entities')
        );
    }
}
