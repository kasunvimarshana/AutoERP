<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;

/**
 * Weighted Average Cost (WAC).
 *
 * A single running cost layer is maintained per product/variant/location.
 * On every inbound receipt the running average is recalculated:
 *   new_avg = (old_qty * old_cost + new_qty * new_cost) / (old_qty + new_qty)
 *
 * On outbound, the current average cost is used for all consumed quantities.
 * The single layer is deducted and the unit_cost remains the running average.
 */
class WeightedAverageValuationStrategy implements ValuationStrategyInterface
{
    public function getMethod(): string
    {
        return 'weighted_average';
    }

    /**
     * For weighted average there is a single running layer.
     * Deduct the full requested quantity from it.
     *
     * @param  InventoryCostLayer[]  $availableLayers  Expected to contain exactly one open layer
     */
    public function consumeLayers(string $quantity, array $availableLayers): array
    {
        if (empty($availableLayers)) {
            throw new InsufficientAvailableStockException(
                'No open cost layer found for weighted-average consumption.',
            );
        }

        $layer = reset($availableLayers);
        $available = $layer->getQuantityRemaining();

        if (bccomp($quantity, $available, 6) > 0) {
            throw new InsufficientAvailableStockException(
                sprintf(
                    'Insufficient stock for weighted-average: requested %s, available %s.',
                    $quantity,
                    $available,
                ),
            );
        }

        $layer->deduct($quantity);

        return [$layer];
    }

    /**
     * Build or describe the updated running layer for a new receipt.
     *
     * If $existingOpenLayers is empty a fresh layer is returned.
     * The caller (ValuationEngineService) is responsible for either creating
     * or updating the layer based on whether one already exists.
     */
    public function buildInboundLayer(array $context): InventoryCostLayer
    {
        return new InventoryCostLayer(
            tenantId: (int) $context['tenant_id'],
            productId: (int) $context['product_id'],
            variantId: isset($context['variant_id']) ? (int) $context['variant_id'] : null,
            batchId: isset($context['batch_id']) ? (int) $context['batch_id'] : null,
            locationId: (int) $context['location_id'],
            valuationMethod: 'weighted_average',
            layerDate: (string) $context['layer_date'],
            quantityIn: (string) $context['quantity'],
            quantityRemaining: (string) $context['quantity'],
            unitCost: (string) $context['unit_cost'],
            referenceType: $context['reference_type'] ?? null,
            referenceId: isset($context['reference_id']) ? (int) $context['reference_id'] : null,
            isClosed: false,
        );
    }

    /**
     * Recalculate the running weighted-average cost.
     *
     * Returns a new layer (or updates the passed-in one) with the correct
     * merged quantity and average unit cost.
     *
     * @param  InventoryCostLayer[]  $existingOpenLayers
     */
    public function recalculateOnReceipt(InventoryCostLayer $newLayer, array $existingOpenLayers): InventoryCostLayer
    {
        if (empty($existingOpenLayers)) {
            return $newLayer;
        }

        $existingLayer = reset($existingOpenLayers);

        $oldQty = $existingLayer->getQuantityRemaining();
        $oldCost = $existingLayer->getUnitCost();
        $newQty = $newLayer->getQuantityIn();
        $newCost = $newLayer->getUnitCost();

        $totalQty = bcadd($oldQty, $newQty, 6);

        if (bccomp($totalQty, '0.000000', 6) <= 0) {
            return $newLayer;
        }

        $totalValue = bcadd(bcmul($oldQty, $oldCost, 6), bcmul($newQty, $newCost, 6), 6);
        $avgCost = bcdiv($totalValue, $totalQty, 6);

        $existingLayer->setQuantityRemaining($totalQty);
        $existingLayer->setUnitCost($avgCost);

        return $existingLayer;
    }
}
