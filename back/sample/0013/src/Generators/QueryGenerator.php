<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class QueryGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'application/query'; }
    public function label(): string   { return 'CQRS Query'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.queries'),
            "{$className}Query.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.queries'));
    }
}
