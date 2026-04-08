<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class QueryHandlerGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'application/query-handler'; }
    public function label(): string   { return 'Query Handler'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.handlers'),
            "{$className}QueryHandler.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.handlers'));
    }

    protected function extraTokens(string $context, string $className, array $options): array
    {
        $appLayer = config('ddd-architect.layers.application');
        $qryDir   = config('ddd-architect.application_directories.queries');
        $ns       = $this->resolver->resolveNamespace($context);

        return [
            'queryNamespace' => "{$ns}\\{$appLayer}\\{$qryDir}",
            'queryName'      => "{$className}Query",
        ];
    }
}
