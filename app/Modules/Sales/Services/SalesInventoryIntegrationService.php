<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockAllocationService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnLine;

final class SalesInventoryIntegrationService
{
    public function __construct(
        private readonly StockAllocationService $allocations,
        private readonly StockMovementService $movements,
    ) {}

    public function allocateForDelivery(SalesDelivery $delivery, SalesDeliveryLine $line): ?InventoryAllocation
    {
        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        return $this->allocations->allocate(new AllocationData(
            tenantId: (int) $delivery->tenant_id,
            allocationDate: $delivery->delivery_date->toDateString(),
            itemId: (int) $line->item_id,
            warehouseId: (int) $delivery->warehouse_id,
            quantityAllocated: (string) $line->delivered_quantity,
            organizationUnitId: $delivery->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $delivery->warehouse_location_id,
            sourceType: 'sales_delivery',
            sourceId: (int) $delivery->getKey(),
            sourceLineType: 'sales_delivery_line',
            sourceLineId: (int) $line->getKey(),
            notes: 'Sales delivery '.$delivery->delivery_number,
        ));
    }

    public function issueAllocation(?InventoryAllocation $allocation): ?InventoryMovement
    {
        if (! $allocation instanceof InventoryAllocation) {
            return null;
        }

        $this->allocations->issue($allocation);

        return InventoryMovement::query()
            ->where('source_type', $allocation->source_type)
            ->where('source_id', $allocation->source_id)
            ->where('source_line_type', $allocation->source_line_type)
            ->where('source_line_id', $allocation->source_line_id)
            ->latest('id')
            ->first();
    }

    public function reverseDelivery(SalesDeliveryLine $line, ?int $userId = null): ?InventoryMovement
    {
        if ($line->inventoryMovement === null) {
            return null;
        }

        return $this->movements->reverse($line->inventoryMovement, $userId);
    }

    public function returnIn(SalesReturn $return, SalesReturnLine $line, ?int $userId = null): ?InventoryMovement
    {
        if ($line->item_id === null || ! $this->affectsStock((int) $line->item_id) || ! (bool) $return->affects_inventory) {
            return null;
        }

        $unitCost = '0.000000';
        if ($line->source_line_type === 'sales_delivery_line' && $line->source_line_id !== null) {
            $deliveryLine = SalesDeliveryLine::query()->with('inventoryMovement')->find($line->source_line_id);
            $unitCost = (string) ($deliveryLine?->inventoryMovement?->unit_cost ?? '0.000000');
        }

        return $this->movements->record(new StockMovementData(
            tenantId: (int) $return->tenant_id,
            movementDate: $return->return_date->toDateString(),
            movementType: InventoryMovementType::ReturnIn,
            direction: InventoryDirection::In,
            itemId: (int) $line->item_id,
            warehouseId: (int) $return->warehouse_id,
            quantity: (string) $line->returned_quantity,
            organizationUnitId: $return->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $return->warehouse_location_id,
            unitCost: $unitCost,
            sourceType: 'sales_return',
            sourceId: (int) $return->getKey(),
            sourceLineType: 'sales_return_line',
            sourceLineId: (int) $line->getKey(),
            description: 'Sales return '.$return->return_number.' ('.$line->condition_status.')',
        ), $userId);
    }

    public function affectsStock(int $itemId): bool
    {
        $item = Item::query()->findOrFail($itemId);

        return (bool) $item->is_stockable
            && ! in_array($item->item_type, [ItemType::Service, ItemType::Labour, ItemType::NonStock], true);
    }
}
