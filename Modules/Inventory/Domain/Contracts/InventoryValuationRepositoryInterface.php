<?php

namespace Modules\Inventory\Domain\Contracts;

interface InventoryValuationRepositoryInterface
{
    /** Return the last valuation entry for the product within the tenant, or null. */
    public function findLastByProduct(string $tenantId, string $productId): ?object;

    /** Persist a new valuation ledger entry. */
    public function create(array $data): object;

    /**
     * Return a paginated list of valuation entries.
     *
     * Supported $filters keys: product_id, movement_type, valuation_method.
     */
    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object;

    /**
     * Return an aggregated valuation report across all products for the tenant.
     *
     * Each row contains: product_id, total_qty, total_value.
     */
    public function valuationReport(string $tenantId): iterable;
}
