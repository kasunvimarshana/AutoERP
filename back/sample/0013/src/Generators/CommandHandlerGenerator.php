<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

final class CommandHandlerGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'application/command-handler'; }
    public function label(): string   { return 'Command Handler'; }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.handlers'),
            "{$className}Handler.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.application'),
            config('ddd-architect.application_directories.handlers'));
    }

    protected function extraTokens(string $context, string $className, array $options): array
    {
        $appLayer  = config('ddd-architect.layers.application');
        $cmdDir    = config('ddd-architect.application_directories.commands');
        $ns        = $this->resolver->resolveNamespace($context);

        return [
            'commandNamespace' => "{$ns}\\{$appLayer}\\{$cmdDir}",
            'commandName'      => "{$className}Command",
        ];
    }
}
