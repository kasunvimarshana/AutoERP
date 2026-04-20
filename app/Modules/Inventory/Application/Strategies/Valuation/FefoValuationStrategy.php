<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;

/**
 * FEFO — First Expired, First Out.
 *
 * Outbound movements consume the layers whose batch expires soonest first.
 * The engine is responsible for providing layers pre-sorted by expiry_date
 * ascending (see CostLayerRepositoryInterface::findOpenLayersByExpiryAsc).
 *
 * Inbound receipts create a new layer (same mechanics as FIFO).
 */
class FefoValuationStrategy implements ValuationStrategyInterface
{
    use ConsumesLayersSequentially;

    public function getMethod(): string
    {
        return 'fefo';
    }

    public function consumeLayers(string $quantity, array $availableLayers): array
    {
        return $this->consumeSequential($quantity, $availableLayers);
    }

    public function buildInboundLayer(array $context): InventoryCostLayer
    {
        return new InventoryCostLayer(
            tenantId: (int) $context['tenant_id'],
            productId: (int) $context['product_id'],
            variantId: isset($context['variant_id']) ? (int) $context['variant_id'] : null,
            batchId: isset($context['batch_id']) ? (int) $context['batch_id'] : null,
            locationId: (int) $context['location_id'],
            valuationMethod: 'fefo',
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
