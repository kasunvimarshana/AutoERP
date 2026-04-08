<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class DtoGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'application/dto'; }
    public function label(): string   { return 'DTO'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.dtos'),
            "{$className}Dto.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.dtos'));
    }
}
