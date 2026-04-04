<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\IssueStockData;

interface IssueStockServiceInterface
{
    /**
     * Outbound stock issue orchestrator.
     *
     * Allocates stock from InventoryLevel records using the configured allocation
     * algorithm, consumes matching InventoryValuationLayers, and confirms the
     * physical issuance on each level.
     *
     * @return array{allocations: array, total_cost: float}
     * @throws \DomainException if insufficient stock or layers
     */
    public function execute(IssueStockData $data): array;
}
