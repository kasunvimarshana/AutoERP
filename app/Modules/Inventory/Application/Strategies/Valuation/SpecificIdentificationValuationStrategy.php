<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;

/**
 * Specific Identification valuation.
 *
 * Each unit is tracked individually (serial-tracked items).  The cost from
 * the specific layer(s) matching the requested serial/batch is used.
 *
 * The engine must pre-filter layers to the specific serial or batch before
 * passing them here.  This strategy simply deducts from whatever layers
 * are provided, first-available order.
 */
class SpecificIdentificationValuationStrategy implements ValuationStrategyInterface
{
    use ConsumesLayersSequentially;

    public function getMethod(): string
    {
        return 'specific';
    }

    public function consumeLayers(string $quantity, array $availableLayers): array
    {
        return $this->consumeSequential($quantity, $availableLayers);
    }

    /**
     * Build a new inbound layer tied to the specific serial/batch.
     */
    public function buildInboundLayer(array $context): InventoryCostLayer
    {
        return new InventoryCostLayer(
            tenantId: (int) $context['tenant_id'],
            productId: (int) $context['product_id'],
            variantId: isset($context['variant_id']) ? (int) $context['variant_id'] : null,
            batchId: isset($context['batch_id']) ? (int) $context['batch_id'] : null,
            locationId: (int) $context['location_id'],
            valuationMethod: 'specific',
            layerDate: (string) $context['layer_date'],
            quantityIn: (string) $context['quantity'],
            quantityRemaining: (string) $context['quantity'],
            unitCost: (string) $context['unit_cost'],
            referenceType: $context['reference_type'] ?? null,
            referenceId: isset($context['reference_id']) ? (int) $context['reference_id'] : null,
            isClosed: false,
        );
    }

    public function recalculateOnReceipt(InventoryCostLayer $newLayer, array $existingOpenLayers): InventoryCostLayer
    {
        return $newLayer;
    }
}
