<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Module Contract Interface
 *
 * All ERP modules must implement this contract to ensure
 * standardized module registration, initialization, and lifecycle management.
 *
 * This follows the Dependency Inversion Principle (SOLID) by defining
 * a contract that high-level module discovery depends on.
 */
interface ModuleContract
{
    /**
     * Get module identifier (unique across the system)
     *
     * @return string Module ID (e.g., 'inventory', 'sales', 'purchasing')
     */
    public function getModuleId(): string;

    /**
     * Get module name (human-readable)
     *
     * @return string Module name (e.g., 'Inventory Management')
     */
    public function getModuleName(): string;

    /**
     * Get module version
     *
     * @return string Semantic version (e.g., '1.0.0')
     */
    public function getModuleVersion(): string;

    /**
     * Get module dependencies
     * Returns array of module IDs this module depends on
     *
     * @return array<string> List of module IDs
     */
    public function getDependencies(): array;

    /**
     * Get module configuration schema
     * Returns metadata defining module capabilities, entities, and features
     *
     * @return array<string, mixed> Module configuration
     */
    public function getModuleConfig(): array;

    /**
     * Register module services and bindings
     * Called during application service registration phase
     *
     * @return void
     */
    public function register();

    /**
     * Bootstrap module
     * Called after all services are registered
     *
     * @return void
     */
    public function boot();

    /**
     * Check if module is enabled
     * Allows runtime enabling/disabling based on tenant configuration
     */
    public function isEnabled(): bool;

    /**
     * Get module permissions
     * Returns all permissions this module defines
     *
     * @return array<string> List of permission keys
     */
    public function getPermissions(): array;

    /**
     * Get module routes configuration
     * Returns route metadata for API and web routes
     *
     * @return array<string, mixed> Route configuration
     */
    public function getRoutes(): array;

    /**
     * Get module event listeners
     * Returns events this module listens to and handlers
     *
     * @return array<string, string|array> Event to listener mapping
     */
    public function getEventListeners(): array;
}
