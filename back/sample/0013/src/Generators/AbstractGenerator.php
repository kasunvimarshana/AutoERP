<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Generators;

use Archify\DddArchitect\Contracts\GeneratorContract;
use Archify\DddArchitect\Support\ContextResolver;
use Archify\DddArchitect\Support\FileGenerator;
use Archify\DddArchitect\Support\GenerationResult;
use Archify\DddArchitect\Support\StubRenderer;
use Illuminate\Support\Str;

/**
 * AbstractGenerator — Base class for all DDD file generators.
 *
 * Extension guide
 * ───────────────
 * To create a custom generator, extend this class and implement:
 *
 *   1. stubKey()       — return the stub filename key (without .stub)
 *   2. label()         — return a human-readable label for console output
 *   3. targetPath()    — return the absolute filesystem path for the new file
 *   4. namespace()     — return the PHP namespace for the generated class
 *
 * Optionally override:
 *   - extraTokens()    — inject additional {{ tokens }} into the stub
 *   - afterGenerate()  — hook called after successful file creation
 *
 * Example:
 *
 *   class PolicyGenerator extends AbstractGenerator
 *   {
 *       public function stubKey(): string  { return 'domain/policy'; }
 *       public function label(): string    { return 'Domain Policy'; }
 *
 *       protected function targetPath(string $context, string $className): string
 *       {
 *           $layer = config('ddd-architect.layers.domain');
 *           $dir   = config('ddd-architect.domain_directories.policies');
 *           return $this->resolver->resolvePath($context)
 *               . "/{$layer}/{$dir}/{$className}Policy.php";
 *       }
 *
 *       protected function namespace(string $context, string $className): string
 *       {
 *           $layer = config('ddd-architect.layers.domain');
 *           $dir   = config('ddd-architect.domain_directories.policies');
 *           return $this->resolver->resolveNamespace($context) . "\\{$layer}\\{$dir}";
 *       }
 *   }
 */
abstract class AbstractGenerator implements GeneratorContract
{
    public function __construct(
        protected readonly StubRenderer  $renderer,
        protected readonly FileGenerator $fileGenerator,
        protected readonly ContextResolver $resolver,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Abstract methods — must be implemented by concrete generators
    // ──────────────────────────────────────────────────────────────────────────

    abstract protected function targetPath(string $context, string $className): string;

    abstract protected function namespace(string $context, string $className): string;

    // ──────────────────────────────────────────────────────────────────────────
    // GeneratorContract
    // ──────────────────────────────────────────────────────────────────────────

    public function generate(string $context, string $className, array $options = []): bool
    {
        $className = Str::studly($className);
        $context   = Str::studly($context);
        $force     = (bool) ($options['force'] ?? false);

        $namespace  = $this->namespace($context, $className);
        $targetPath = $this->targetPath($context, $className);

        $tokens = $this->renderer->buildTokens(
            $context,
            $className,
            $namespace,
            $this->extraTokens($context, $className, $options)
        );

        $content = $this->renderer->render($this->stubKey(), $tokens);
        $result  = $this->fileGenerator->write($targetPath, $content, $force);

        $this->afterGenerate($result, $context, $className, $options);

        return $result->wasCreated() || $result->isDryRun();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Overridable hooks
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return additional tokens to merge into the stub rendering.
     * Override this to inject generator-specific tokens.
     */
    protected function extraTokens(string $context, string $className, array $options): array
    {
        return [];
    }

    /**
     * Called after the file write operation completes.
     * Override to add post-generation side effects (e.g. registering bindings).
     */
    protected function afterGenerate(
        GenerationResult $result,
        string $context,
        string $className,
        array $options
    ): void {}

    // ──────────────────────────────────────────────────────────────────────────
    // Helper methods for subclasses
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build a namespace from context + layer + subdirectory.
     */
    protected function buildNamespace(string $context, string $layer, string $subDir = ''): string
    {
        $ns = $this->resolver->resolveNamespace($context) . '\\' . $layer;

        if ($subDir !== '') {
            $ns .= '\\' . str_replace('/', '\\', $subDir);
        }

        return $ns;
    }

    /**
     * Build an absolute path: contextBase / layer / subDir / className.php
     */
    protected function buildPath(string $context, string $layer, string $subDir, string $filename): string
    {
        $base = $this->resolver->resolvePath($context);
        $parts = array_filter([$base, $layer, $subDir, $filename]);

        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}
