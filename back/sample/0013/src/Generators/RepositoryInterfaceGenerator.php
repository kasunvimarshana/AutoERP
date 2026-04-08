<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class RepositoryInterfaceGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'domain/repository-interface'; }
    public function label(): string   { return 'Repository Interface'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.repositories'),
            "{$className}RepositoryInterface.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.domain'),
            config('ddd-architect.domain_directories.repositories'));
    }
}
