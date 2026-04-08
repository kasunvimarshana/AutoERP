<?php

namespace YourVendor\LaravelDDDArchitect\Contracts;

interface ContextRegistrar
{
    /**
     * Return all discovered bounded context names.
     */
    public function all(): array;

    /**
     * Check whether a bounded context already exists on disk.
     */
    public function exists(string $context): bool;

    /**
     * Return the absolute filesystem path for a bounded context.
     */
    public function path(string $context): string;

    /**
     * Return the fully-qualified PHP namespace for a bounded context.
     */
    public function namespace(string $context): string;

    /**
     * Return the configured package settings array.
     */
    public function config(): array;
}
