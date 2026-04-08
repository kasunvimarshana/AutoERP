<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

use Archify\DddArchitect\Support\ContextResolver;
use Archify\DddArchitect\Support\FileGenerator;
use Archify\DddArchitect\Support\StubRenderer;
use Illuminate\Support\Str;

/**
 * ContextScaffoldGenerator — Scaffolds the full bounded context directory tree
 * and generates the context's ServiceProvider stub.
 *
 * Directories created follow config('ddd-architect.*_directories') so the
 * structure is entirely driven by the published configuration file.
 */
final class ContextScaffoldGenerator extends AbstractGenerator
{
    public function stubKey(): string { return 'infrastructure/provider'; }
    public function label(): string   { return 'Bounded Context'; }

    public function generate(string $context, string $className, array $options = []): bool
    {
        $context = Str::studly($context);
        $force   = (bool) ($options['force'] ?? false);

        $this->createDirectoryTree($context);
        $this->generateProvider($context, $force);

        return true;
    }

    protected function targetPath(string $context, string $className): string
    {
        return $this->buildPath($context,
            config('ddd-architect.layers.infrastructure'),
            config('ddd-architect.infrastructure_directories.providers'),
            "{$context}ServiceProvider.php");
    }

    protected function namespace(string $context, string $className): string
    {
        return $this->buildNamespace($context,
            config('ddd-architect.layers.infrastructure'),
            config('ddd-architect.infrastructure_directories.providers'));
    }

    // -------------------------------------------------------------------------

    private function createDirectoryTree(string $context): void
    {
        $base = $this->resolver->resolvePath($context);

        $domain         = config('ddd-architect.layers.domain');
        $application    = config('ddd-architect.layers.application');
        $infrastructure = config('ddd-architect.layers.infrastructure');
        $presentation   = config('ddd-architect.layers.presentation');

        $domainDirs = config('ddd-architect.domain_directories');
        $appDirs    = config('ddd-architect.application_directories');
        $infraDirs  = config('ddd-architect.infrastructure_directories');
        $presDirs   = config('ddd-architect.presentation_directories');

        $paths = [];

        foreach ($domainDirs as $dir) {
            $paths[] = "{$base}/{$domain}/{$dir}";
        }
        foreach ($appDirs as $dir) {
            $paths[] = "{$base}/{$application}/{$dir}";
        }
        foreach ($infraDirs as $dir) {
            $paths[] = "{$base}/{$infrastructure}/{$dir}";
        }
        foreach ($presDirs as $dir) {
            $paths[] = "{$base}/{$presentation}/{$dir}";
        }

        foreach ($paths as $path) {
            $this->fileGenerator->ensureDirectory($path);
            // Place a .gitkeep so Git tracks empty directories
            $gitkeep = $path . '/.gitkeep';
            if (! file_exists($gitkeep)) {
                file_put_contents($gitkeep, '');
            }
        }
    }

    private function generateProvider(string $context, bool $force): void
    {
        $namespace = $this->namespace($context, $context);
        $tokens    = $this->renderer->buildTokens($context, $context, $namespace, [
            'contextNamespace' => $this->resolver->resolveNamespace($context),
        ]);

        $content = $this->renderer->render($this->stubKey(), $tokens);
        $path    = $this->targetPath($context, $context);

        $this->fileGenerator->write($path, $content, $force);
    }
}
