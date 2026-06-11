<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemBaseUomConversionService;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnLine;

final class SalesInventoryIntegrationService
{
    public function __construct(
        private readonly InventoryFacade $inventory,
        private readonly ItemBaseUomConversionService $baseUomConversions,
        private readonly DecimalMath $math,
    ) {}

    public function allocateForDelivery(SalesDelivery $delivery, SalesDeliveryLine $line): ?InventoryAllocation
    {
        if (! $this->affectsStock((int) $line->item_id)) {
            return null;
        }

        $item = Item::query()->findOrFail($line->item_id);
        $quantity = $this->baseUomConversions->convertOperationalBasis(
            $item,
            (int) ($line->uom_id ?: $item->base_uom_id),
            (string) $line->delivered_quantity,
            (string) $line->unit_price,
        )['quantity'];

        return $this->inventory->allocate(new AllocationData(
            tenantId: (int) $delivery->tenant_id,
            allocationDate: $delivery->delivery_date->toDateString(),
            itemId: (int) $line->item_id,
            warehouseId: (int) $delivery->warehouse_id,
            quantityAllocated: $quantity,
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

        $allocation = $this->inventory->issueAllocation($allocation);

        return $allocation->issues()->with('movement')->latest('id')->first()?->movement;
    }

    public function reverseDelivery(SalesDeliveryLine $line, ?int $userId = null): ?InventoryMovement
    {
        $issues = InventoryAllocationIssue::query()
            ->whereHas('allocation', fn ($query) => $query
                ->where('source_line_type', 'sales_delivery_line')
                ->where('source_line_id', $line->getKey()))
            ->with('movement')
            ->orderBy('id')
            ->get();
        if ($issues->isEmpty()) {
            return $line->inventoryMovement === null
                ? null
                : $this->inventory->reverse($line->inventoryMovement, $userId);
        }

        $first = null;
        foreach ($issues as $issue) {
            if ($issue->movement instanceof InventoryMovement) {
                $reversal = $this->inventory->reverse($issue->movement, $userId);
                $first ??= $reversal;
            }
        }

        return $first;
    }

    public function returnIn(SalesReturn $return, SalesReturnLine $line, ?int $userId = null): ?InventoryMovement
    {
        if ($line->item_id === null || ! $this->affectsStock((int) $line->item_id) || ! (bool) $return->affects_inventory) {
            return null;
        }

        $unitCost = '0.000000';
        if ($line->source_line_type === 'sales_delivery_line' && $line->source_line_id !== null) {
            $issues = InventoryAllocationIssue::query()
                ->whereHas('allocation', fn ($query) => $query
                    ->where('source_line_type', 'sales_delivery_line')
                    ->where('source_line_id', $line->source_line_id))
                ->get();
            $issuedQuantity = $this->math->sum($issues->pluck('quantity_issued')->all());
            $issuedCost = $this->math->sum($issues->pluck('total_cost')->all());
            if (! $this->math->isZero($issuedQuantity)) {
                $unitCost = $this->math->div($issuedCost, $issuedQuantity);
            } else {
                $deliveryLine = SalesDeliveryLine::query()->with('inventoryMovement')->find($line->source_line_id);
                $unitCost = (string) ($deliveryLine?->inventoryMovement?->unit_cost ?? '0.000000');
            }
        }

        $item = Item::query()->findOrFail($line->item_id);
        $basis = $this->baseUomConversions->convertOperationalBasis(
            $item,
            (int) ($line->uom_id ?: $item->base_uom_id),
            (string) $line->returned_quantity,
            $unitCost,
        );

        return $this->inventory->receive(new StockMovementData(
            tenantId: (int) $return->tenant_id,
            movementDate: $return->return_date->toDateString(),
            movementType: InventoryMovementType::ReturnIn,
            direction: InventoryDirection::In,
            itemId: (int) $line->item_id,
            warehouseId: (int) $return->warehouse_id,
            quantity: $basis['quantity'],
            organizationUnitId: $return->organization_unit_id,
            itemVariantId: $line->item_variant_id,
            warehouseLocationId: $return->warehouse_location_id,
            unitCost: $basis['unit_cost'],
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
