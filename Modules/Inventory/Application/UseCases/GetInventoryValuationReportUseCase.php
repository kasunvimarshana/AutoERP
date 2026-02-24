<?php

namespace Modules\Inventory\Application\UseCases;

use Modules\Inventory\Domain\Contracts\InventoryValuationRepositoryInterface;

/**
 * Returns the current inventory valuation report for a tenant.
 *
 * Each row contains: product_id, running_balance_qty, running_balance_value.
 * Values are taken from the latest valuation entry per product (immutable ledger).
 */
class GetInventoryValuationReportUseCase
{
    public function __construct(
        private InventoryValuationRepositoryInterface $valuationRepo,
    ) {}

    public function execute(string $tenantId): iterable
    {
        return $this->valuationRepo->valuationReport($tenantId);
    }
}
