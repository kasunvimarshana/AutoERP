<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;

/**
 * Strategy contract for inventory valuation methods.
 *
 * Implementations determine how cost is assigned to outbound movements
 * and how new inbound cost layers are created.
 */
interface ValuationStrategyInterface
{
    /**
     * Return the identifier matching the valuation_method enum values.
     */
    public function getMethod(): string;

    /**
     * Select and consume cost layers for an outbound movement.
     *
     * Returns updated cost layers with their consumed quantities recorded.
     * The sum of consumed quantities across all returned layers equals
     * the movement's total quantity.
     *
     * @param  InventoryCostLayer[]  $availableLayers  Open layers ordered by the strategy's selection rule
     * @return InventoryCostLayer[] Layers with deducted quantity_remaining
     *
     * @throws InsufficientAvailableStockException
     */
    public function consumeLayers(string $quantity, array $availableLayers): array;

    /**
     * Build a new inbound cost layer for a receipt movement.
     *
     * @param  array<string, mixed>  $context  tenant_id, product_id, variant_id, batch_id, location_id, layer_date, unit_cost, quantity, reference_type, reference_id
     */
    public function buildInboundLayer(array $context): InventoryCostLayer;

    /**
     * Recalculate the unit cost after a receipt for strategies that maintain
     * a running average (e.g. weighted average).
     *
     * For layer-based strategies (FIFO/LIFO/FEFO) this is a no-op.
     *
     * @param  InventoryCostLayer[]  $existingOpenLayers
     */
    public function recalculateOnReceipt(InventoryCostLayer $newLayer, array $existingOpenLayers): InventoryCostLayer;
}
