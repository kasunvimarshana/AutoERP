<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Support;

use Archify\DddArchitect\Contracts\ContextRegistrar;
use Illuminate\Support\Str;

/**
 * ContextResolver — Concrete implementation of ContextRegistrar.
 *
 * Stores bounded context metadata in memory and provides resolution
 * helpers used by generators and the ServiceProvider auto-discovery.
 */
final class ContextResolver implements ContextRegistrar
{
    /** @var array<string, array> */
    private array $contexts = [];

    // ──────────────────────────────────────────────────────────────────────────
    // ContextRegistrar implementation
    // ──────────────────────────────────────────────────────────────────────────

    public function register(string $name, array $metadata = []): void
    {
        $name = Str::studly($name);

        $this->contexts[$name] = array_merge([
            'name'      => $name,
            'kebab'     => Str::kebab($name),
            'snake'     => Str::snake($name),
            'path'      => null,
            'namespace' => null,
            'provider'  => null,
        ], $metadata);
    }

    public function get(string $name): ?array
    {
        return $this->contexts[Str::studly($name)] ?? null;
    }

    public function all(): array
    {
        return $this->contexts;
    }

    public function has(string $name): bool
    {
        return isset($this->contexts[Str::studly($name)]);
    }

    public function forget(string $name): void
    {
        unset($this->contexts[Str::studly($name)]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Resolution helpers (used by generators)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolve the filesystem base path for a given context.
     */
    public function resolvePath(string $context): string
    {
        $mode       = config('ddd-architect.mode', 'layered');
        $basePath   = config("ddd-architect.paths.{$mode}", base_path('src'));
        $studly     = Str::studly($context);

        return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $studly;
    }

    /**
     * Resolve the PSR-4 namespace root for a given context.
     */
    public function resolveNamespace(string $context): string
    {
        $mode      = config('ddd-architect.mode', 'layered');
        $nsRoot    = config("ddd-architect.namespaces.{$mode}", 'App');
        $studly    = Str::studly($context);

        $mode === 'modular'
            ? $ns = "{$nsRoot}\\{$studly}"
            : $ns = "{$nsRoot}\\{$studly}";

        return $ns;
    }

    /**
     * Resolve the provider class name for a given context, using the configured pattern.
     */
    public function resolveProvider(string $context): string
    {
        $pattern   = config('ddd-architect.provider_pattern');
        $namespace = $this->resolveNamespace($context);
        $studly    = Str::studly($context);

        return str_replace(
            ['{namespace}', '{context}'],
            [$namespace, $studly],
            $pattern
        );
    }
}
