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
use Modules\Sales\Enums\SalesAllocationStatus;
use Modules\Sales\Models\SalesAllocationLine;
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

        return $this->inventory->allocate(new AllocationData(
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
            uomId: $line->uom_id,
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

    /**
     * @return array{allocation: InventoryAllocation|null, movement: InventoryMovement|null, allocated_now: bool}
     */
    public function issueForDelivery(SalesDelivery $delivery, SalesDeliveryLine $line, ?int $userId = null): array
    {
        if (! $this->affectsStock((int) $line->item_id)) {
            return ['allocation' => null, 'movement' => null, 'allocated_now' => false];
        }

        $allocation = $this->existingOrderAllocation($line, (string) $line->delivered_quantity);
        $allocatedNow = false;
        if (! $allocation instanceof InventoryAllocation) {
            $allocation = $this->allocateForDelivery($delivery, $line);
            $allocatedNow = true;
        }

        $allocation = $this->inventory->issueAllocation($allocation, (string) $line->delivered_quantity, $userId);
        $this->markSalesAllocationIssued($allocation, (string) $line->delivered_quantity);

        return [
            'allocation' => $allocation,
            'movement' => $allocation->issues()->with('movement')->latest('id')->first()?->movement,
            'allocated_now' => $allocatedNow,
        ];
    }

    public function reverseDelivery(SalesDeliveryLine $line, ?int $userId = null): ?InventoryMovement
    {
        $issues = InventoryAllocationIssue::query()
            ->where(function ($query) use ($line): void {
                $query->whereHas('allocation', fn ($allocation) => $allocation
                    ->where('source_line_type', 'sales_delivery_line')
                    ->where('source_line_id', $line->getKey()));
                if ($line->inventory_movement_id !== null) {
                    $query->orWhere('movement_id', $line->inventory_movement_id);
                }
            })
            ->with(['movement', 'allocation'])
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
                if ($issue->allocation instanceof InventoryAllocation) {
                    $this->markSalesAllocationUnissued($issue->allocation, (string) $issue->quantity_issued);
                }
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
        $uomId = (int) ($line->uom_id ?: $item->base_uom_id);
        $factor = $this->baseUomConversions->factorToCurrentBase($item, $uomId);
        $enteredUnitCost = $this->math->mul($unitCost, $factor);

        return $this->inventory->receive(new StockMovementData(
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
            unitCost: $enteredUnitCost,
            sourceType: 'sales_return',
            sourceId: (int) $return->getKey(),
            sourceLineType: 'sales_return_line',
            sourceLineId: (int) $line->getKey(),
            description: 'Sales return '.$return->return_number.' ('.$line->condition_status.')',
            uomId: $uomId,
        ), $userId);
    }

    public function affectsStock(int $itemId): bool
    {
        $item = Item::query()->findOrFail($itemId);

        return (bool) $item->is_stockable
            && ! in_array($item->item_type, [ItemType::Service, ItemType::Labour, ItemType::NonStock], true);
    }

    private function existingOrderAllocation(SalesDeliveryLine $line, string $quantity): ?InventoryAllocation
    {
        if ($line->sales_order_line_id === null) {
            return null;
        }

        return InventoryAllocation::query()
            ->where('tenant_id', $line->tenant_id)
            ->where('source_type', 'sales_order')
            ->where('source_line_type', 'sales_order_line')
            ->where('source_line_id', $line->sales_order_line_id)
            ->whereRaw('quantity_remaining >= ?', [$quantity])
            ->where('status', 'active')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function markSalesAllocationIssued(InventoryAllocation $allocation, string $quantity): void
    {
        $line = SalesAllocationLine::query()
            ->where('inventory_allocation_id', $allocation->getKey())
            ->lockForUpdate()
            ->first();

        if (! $line instanceof SalesAllocationLine) {
            return;
        }

        $line->issued_quantity = $this->math->add((string) $line->issued_quantity, $quantity);
        $line->status = $this->math->compare((string) $line->issued_quantity, (string) $line->allocated_quantity) >= 0
            ? SalesAllocationStatus::Issued
            : SalesAllocationStatus::Active;
        $line->save();

        $allocationHeader = $line->allocation;
        if ($allocationHeader !== null) {
            $allocationHeader->load('lines');
            $allIssued = $allocationHeader->lines->every(
                fn (SalesAllocationLine $row): bool => $this->math->compare((string) $row->issued_quantity, (string) $row->allocated_quantity) >= 0,
            );
            if ($allIssued) {
                $allocationHeader->status = SalesAllocationStatus::Issued;
                $allocationHeader->save();
            }
        }
    }

    private function markSalesAllocationUnissued(InventoryAllocation $allocation, string $quantity): void
    {
        $line = SalesAllocationLine::query()
            ->where('inventory_allocation_id', $allocation->getKey())
            ->lockForUpdate()
            ->first();

        if (! $line instanceof SalesAllocationLine) {
            return;
        }

        $line->issued_quantity = $this->math->sub((string) $line->issued_quantity, $quantity);
        if ($this->math->isNegative((string) $line->issued_quantity)) {
            $line->issued_quantity = '0.000000';
        }
        $line->status = SalesAllocationStatus::Active;
        $line->save();

        $allocationHeader = $line->allocation;
        if ($allocationHeader !== null && $allocationHeader->status === SalesAllocationStatus::Issued) {
            $allocationHeader->status = SalesAllocationStatus::Active;
            $allocationHeader->save();
        }
    }
}
