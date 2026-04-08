<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class EloquentRepositoryGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'infrastructure/eloquent-repository'; }
    public function label(): string   { return 'Eloquent Repository'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.infrastructure'),
            config('ddd-architect.infrastructure_directories.repositories'),
            "Eloquent{$className}Repository.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.infrastructure'),
            config('ddd-architect.infrastructure_directories.repositories'));
    }

    protected function extraTokens(string $context, string $className, array $options): array
    {
        $domainLayer = config('ddd-architect.layers.domain');
        $repoDir     = config('ddd-architect.domain_directories.repositories');
        $ns          = $this->resolver->resolveNamespace($context);

        return [
            'interfaceNamespace' => "{$ns}\\{$domainLayer}\\{$repoDir}",
            'interfaceName'      => "{$className}RepositoryInterface",
        ];
    }
}
