<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use Modules\Inventory\Domain\Contracts\ValuationStrategyInterface;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;

/**
 * LIFO — Last In, First Out.
 *
 * Outbound movements consume the newest layers first.
 * Inbound receipts add a new layer (same as FIFO — layers are just selected
 * in reverse order on outbound).
 */
class LifoValuationStrategy implements ValuationStrategyInterface
{
    use ConsumesLayersSequentially;

    public function getMethod(): string
    {
        return 'lifo';
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
            valuationMethod: 'lifo',
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
