<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Inter-Module Communication Contract
 *
 * Defines how modules communicate with each other in the AutoERP system.
 * Ensures loose coupling between modules while enabling required integrations.
 *
 * This contract enables:
 * - Sales module to check inventory availability
 * - Accounting module to create invoices from sales orders
 * - Purchasing module to trigger inventory updates
 *
 * @package Modules\Core\Contracts
 */
interface InterModuleContract
{
    /**
     * Get module identifier
     *
     * @return string Module name (e.g., 'inventory', 'sales', 'accounting')
     */
    public function getModuleIdentifier(): string;

    /**
     * Get module version
     *
     * @return string Semantic version (e.g., '1.0.0')
     */
    public function getModuleVersion(): string;

    /**
     * Get list of modules this module depends on
     *
     * @return array<string> Array of module identifiers
     */
    public function getDependencies(): array;

    /**
     * Get public API methods exposed to other modules
     *
     * @return array<string, string> Array of method names and descriptions
     */
    public function getPublicApi(): array;

    /**
     * Check if module can handle specific operation
     *
     * @param string $operation Operation identifier
     * @return bool
     */
    public function canHandle(string $operation): bool;

    /**
     * Execute cross-module operation
     *
     * @param string $operation
     * @param array<string, mixed> $parameters
     * @return mixed
     * @throws \RuntimeException If operation not supported
     */
    public function execute(string $operation, array $parameters = []);
}
