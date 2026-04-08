<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

use Archify\DddArchitect\Contracts\GeneratorContract;
use Archify\DddArchitect\Support\FileGenerator;
use Archify\DddArchitect\Support\StubRenderer;

/**
 * SharedKernelGenerator — Scaffolds the Shared Kernel on first context creation.
 *
 * Generates:
 *   Shared/Domain/Contracts/AggregateRootContract.php
 *   Shared/Domain/Contracts/EntityContract.php
 *   Shared/Domain/Contracts/RepositoryContract.php
 *   Shared/Domain/ValueObjects/Uuid.php
 *   Shared/Domain/ValueObjects/Email.php
 *   Shared/Domain/ValueObjects/Money.php
 */
final class SharedKernelGenerator implements GeneratorContract
{
    private array $files = [
        ['stub' => 'shared/aggregate-root-contract', 'subPath' => 'Contracts/AggregateRootContract.php'],
        ['stub' => 'shared/entity-contract',         'subPath' => 'Contracts/EntityContract.php'],
        ['stub' => 'shared/repository-contract',     'subPath' => 'Contracts/RepositoryContract.php'],
        ['stub' => 'shared/uuid',                    'subPath' => 'ValueObjects/Uuid.php'],
        ['stub' => 'shared/email',                   'subPath' => 'ValueObjects/Email.php'],
        ['stub' => 'shared/money',                   'subPath' => 'ValueObjects/Money.php'],
    ];

    public function __construct(
        private readonly StubRenderer  $renderer,
        private readonly FileGenerator $fileGenerator,
    ) {}

    public function generate(string $context, string $className, array $options = []): bool
    {
        if (! config('ddd-architect.shared_kernel.auto_scaffold', true)) {
            return false;
        }

        $force     = (bool) ($options['force'] ?? false);
        $basePath  = config('ddd-architect.shared_kernel.path', base_path('src/Shared'));
        $namespace = config('ddd-architect.shared_kernel.namespace', 'App\\Shared');
        $domain    = config('ddd-architect.layers.domain', 'Domain');

        foreach ($this->files as $file) {
            $path    = "{$basePath}/{$domain}/{$file['subPath']}";
            $tokens  = $this->buildTokens($namespace, $domain, $file['subPath']);
            $content = $this->renderer->render($file['stub'], $tokens);

            $this->fileGenerator->write($path, $content, $force);
        }

        return true;
    }

    public function stubKey(): string { return 'shared/*'; }
    public function label(): string   { return 'Shared Kernel'; }

    private function buildTokens(string $namespace, string $domain, string $subPath): array
    {
        $parts     = explode('/', $subPath);
        $subDir    = $parts[0] ?? '';
        $fullNs    = "{$namespace}\\{$domain}\\{$subDir}";

        return [
            'namespace'      => $fullNs,
            'rootNamespace'  => $namespace,
            'date'           => date('Y-m-d'),
            'year'           => date('Y'),
        ];
    }
}
