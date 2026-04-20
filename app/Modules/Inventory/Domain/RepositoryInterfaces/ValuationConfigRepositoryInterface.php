<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\ValuationConfig;

interface ValuationConfigRepositoryInterface
{
    /**
     * Persist a new valuation config and return it with its assigned id.
     */
    public function create(ValuationConfig $config): ValuationConfig;

    /**
     * Persist changes to an existing config.
     */
    public function update(ValuationConfig $config): ValuationConfig;

    /**
     * Delete a valuation config by id.
     */
    public function delete(int $tenantId, int $id): void;

    /**
     * Find a single config by id within a tenant.
     */
    public function findById(int $tenantId, int $id): ?ValuationConfig;

    /**
     * Resolve the effective valuation config for a given context using
     * scope precedence: product > warehouse > org_unit > tenant.
     *
     * Returns null when no active config exists for any scope.
     */
    public function resolveEffective(
        int $tenantId,
        ?int $productId = null,
        ?int $warehouseId = null,
        ?int $orgUnitId = null,
        ?string $transactionType = null,
    ): ?ValuationConfig;

    /**
     * Paginate all configs belonging to the tenant.
     */
    public function paginate(int $tenantId, int $perPage = 15, int $page = 1): mixed;
}
