<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Contracts;

/**
 * ContextRegistrar — Registry contract for bounded contexts.
 *
 * Implementations must provide a way to register, retrieve, and list
 * all bounded contexts discovered or manually registered within the project.
 */
interface ContextRegistrar
{
    /**
     * Register a new bounded context by name.
     *
     * @param  string  $name      PascalCase context name (e.g. "Ordering")
     * @param  array   $metadata  Optional metadata (path, namespace, provider, …)
     */
    public function register(string $name, array $metadata = []): void;

    /**
     * Retrieve metadata for a single context.
     *
     * @param  string  $name
     * @return array|null  Returns null when the context is not registered
     */
    public function get(string $name): ?array;

    /**
     * Return all registered contexts as name => metadata map.
     *
     * @return array<string, array>
     */
    public function all(): array;

    /**
     * Determine whether a context has been registered.
     */
    public function has(string $name): bool;

    /**
     * Remove a context from the registry (mainly for testing).
     */
    public function forget(string $name): void;
}
