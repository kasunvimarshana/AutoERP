<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Interface ModuleInterface
 *
 * Defines the contract for all plugin-style modules in the system.
 * Each module must be independently installable, removable, and extendable.
 */
interface ModuleInterface
{
    /**
     * Get the module name
     */
    public function getName(): string;

    /**
     * Get the module version
     */
    public function getVersion(): string;

    /**
     * Get module dependencies (other module names)
     *
     * @return array<string>
     */
    public function getDependencies(): array;

    /**
     * Check if the module is enabled
     */
    public function isEnabled(): bool;

    /**
     * Boot the module
     */
    public function boot(): void;

    /**
     * Register the module services
     */
    public function register(): void;

    /**
     * Get module configuration
     */
    public function getConfig(): array;
}
