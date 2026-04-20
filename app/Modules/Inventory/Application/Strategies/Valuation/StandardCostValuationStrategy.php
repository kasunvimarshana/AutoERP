<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;

/**
 * Standard Cost valuation.
 *
 * All movements use a pre-defined standard cost stored on the product.
 * The standard cost is passed in via the context array key 'standard_cost'.
 *
 * Variance between actual cost and standard cost is posted to a variance
 * account at the service layer (out of scope for this strategy).
 *
 * A single "always-open" layer is maintained per product/variant/location
 * at the standard cost.  The layer quantity mirrors stock_levels on hand.
 */
class StandardCostValuationStrategy implements ValuationStrategyInterface
{
    public function getMethod(): string
    {
        return 'standard';
    }

    /**
     * Consume using the standard cost layer (treated like weighted average —
     * one layer per location, deducted on outbound).
     *
     * @param  InventoryCostLayer[]  $availableLayers
     */
    public function consumeLayers(string $quantity, array $availableLayers): array
    {
        if (empty($availableLayers)) {
            throw new InsufficientAvailableStockException(
                'No open cost layer found for standard-cost consumption.',
            );
        }

        $layer = reset($availableLayers);
        $available = $layer->getQuantityRemaining();

        if (bccomp($quantity, $available, 6) > 0) {
            throw new InsufficientAvailableStockException(
                sprintf(
                    'Insufficient stock for standard-cost: requested %s, available %s.',
                    $quantity,
                    $available,
                ),
            );
        }

        $layer->deduct($quantity);

        return [$layer];
    }

    /**
     * Build a new inbound layer at the standard cost.
     *
     * The 'unit_cost' in $context should already be the product's standard cost.
     */
    public function buildInboundLayer(array $context): InventoryCostLayer
    {
        return new InventoryCostLayer(
            tenantId: (int) $context['tenant_id'],
            productId: (int) $context['product_id'],
            variantId: isset($context['variant_id']) ? (int) $context['variant_id'] : null,
            batchId: isset($context['batch_id']) ? (int) $context['batch_id'] : null,
            locationId: (int) $context['location_id'],
            valuationMethod: 'standard',
            layerDate: (string) $context['layer_date'],
            quantityIn: (string) $context['quantity'],
            quantityRemaining: (string) $context['quantity'],
            unitCost: isset($context['standard_cost']) ? (string) $context['standard_cost'] : (string) $context['unit_cost'],
            referenceType: $context['reference_type'] ?? null,
            referenceId: isset($context['reference_id']) ? (int) $context['reference_id'] : null,
            isClosed: false,
        );
    }

    /**
     * For standard cost, receipt of new stock increases the layer quantity
     * but the unit_cost remains the standard cost (unchanged).
     *
     * @param  InventoryCostLayer[]  $existingOpenLayers
     */
    public function recalculateOnReceipt(InventoryCostLayer $newLayer, array $existingOpenLayers): InventoryCostLayer
    {
        if (empty($existingOpenLayers)) {
            return $newLayer;
        }

        $existingLayer = reset($existingOpenLayers);
        $mergedQty = bcadd($existingLayer->getQuantityRemaining(), $newLayer->getQuantityIn(), 6);
        $existingLayer->setQuantityRemaining($mergedQty);

        return $existingLayer;
    }
}
