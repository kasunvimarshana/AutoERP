<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\CostLayerInboundData;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;

/**
 * Orchestrates inventory valuation: applies the configured strategy to
 * inbound receipts (building cost layers) and outbound movements (consuming
 * layers and calculating the weighted cost of goods sold).
 */
interface ValuationEngineServiceInterface
{
    /**
     * Process an inbound receipt: build a cost layer and persist it.
     *
     * For weighted-average costing the existing running layer is updated
     * instead of a new layer being created.
     */
    public function processInbound(CostLayerInboundData $dto): InventoryCostLayer;

    /**
     * Process an outbound movement: consume layers according to the
     * configured valuation strategy and return the updated layers.
     *
     * @param  string  $valuationMethod  Resolved method (fifo|lifo|fefo|weighted_average|standard|specific)
     * @return InventoryCostLayer[]
     */
    public function processOutbound(
        int $tenantId,
        int $productId,
        ?int $variantId,
        int $locationId,
        string $quantity,
        string $valuationMethod,
    ): array;

    /**
     * Resolve the effective valuation method for a given context.
     *
     * Falls back from product → warehouse → org_unit → tenant → system default.
     */
    public function resolveValuationMethod(
        int $tenantId,
        ?int $productId = null,
        ?int $warehouseId = null,
        ?int $orgUnitId = null,
        ?string $transactionType = null,
    ): string;
}
