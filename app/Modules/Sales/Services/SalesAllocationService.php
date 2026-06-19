<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Sales\DTOs\CreateSalesAllocationData;
use Modules\Sales\Enums\SalesAllocationStatus;
use Modules\Sales\Enums\SalesOrderLineStatus;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Sales\Models\SalesAllocation;
use Modules\Sales\Models\SalesAllocationLine;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Validators\SalesValidationService;

final class SalesAllocationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly SalesValidationService $validator,
        private readonly SalesFulfilmentBalanceService $balances,
        private readonly SalesDocumentLockService $locks,
        private readonly SalesOrderService $orders,
        private readonly SalesNumberService $numbers,
        private readonly InventoryFacade $inventory,
    ) {}

    public function create(CreateSalesAllocationData $data): SalesAllocation
    {
        if ($data->lines === []) {
            throw new InvalidArgumentException('Sales allocation requires at least one line.');
        }

        $order = SalesOrder::query()->with(['lines'])->findOrFail($data->salesOrderId);
        $this->validator->assertTenantOrg((int) $order->tenant_id, $order->organization_unit_id, $data->tenantId, $data->organizationUnitId);
        if ($order->status !== SalesOrderStatus::Approved) {
            throw new InvalidArgumentException('Sales allocation requires an approved sales order.');
        }
        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        if ($data->warehouseLocationId !== null) {
            $this->validator->warehouseLocation($data->tenantId, $data->organizationUnitId, $data->warehouseId, $data->warehouseLocationId);
        }

        return DB::transaction(function () use ($data, $order): SalesAllocation {
            $order = $this->locks->salesOrders([(int) $order->getKey()])->firstOrFail();
            $lineIds = array_map(static fn ($line): int => $line->salesOrderLineId, $data->lines);
            $lockedLines = $this->locks->salesOrderLines($lineIds)->keyBy('id');

            $allocation = SalesAllocation::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'allocation_number' => $data->allocationNumber ?? $this->numbers->next(
                    $data->tenantId,
                    $data->organizationUnitId,
                    'allocation',
                    $data->allocationDate,
                    'SA',
                ),
                'allocation_date' => $data->allocationDate,
                'sales_order_id' => $order->getKey(),
                'customer_id' => $order->customer_id,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'status' => SalesAllocationStatus::Active,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            $lineNumber = 1;
            foreach ($data->lines as $lineData) {
                $line = $lockedLines->get($lineData->salesOrderLineId);
                if (! $line instanceof SalesOrderLine) {
                    throw new InvalidArgumentException('Sales allocation line source was not found.');
                }
                if ((int) $line->sales_order_id !== (int) $order->getKey()) {
                    throw new InvalidArgumentException('Sales allocation lines must belong to the selected sales order.');
                }
                $this->validator->assertPositive($lineData->quantity, 'Allocated quantity must be greater than zero.');
                $remaining = $this->balances->remainingAllocatableForSalesOrderLine($line);
                if ($this->math->compare($lineData->quantity, $remaining) > 0) {
                    throw new InvalidArgumentException('Allocated quantity cannot exceed sales order remaining allocatable quantity.');
                }

                $inventoryAllocation = $this->inventory->allocate(new AllocationData(
                    tenantId: $data->tenantId,
                    allocationDate: $data->allocationDate,
                    itemId: (int) $line->item_id,
                    warehouseId: $data->warehouseId,
                    quantityAllocated: $lineData->quantity,
                    organizationUnitId: $data->organizationUnitId,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $data->warehouseLocationId,
                    sourceType: 'sales_order',
                    sourceId: (int) $order->getKey(),
                    sourceLineType: 'sales_order_line',
                    sourceLineId: (int) $line->getKey(),
                    notes: 'Sales allocation '.$allocation->allocation_number,
                    uomId: $line->ordered_uom_id,
                    createdBy: $data->createdBy,
                ));

                $allocation->lines()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'sales_order_line_id' => $line->getKey(),
                    'line_number' => $lineNumber++,
                    'item_id' => $line->item_id,
                    'item_variant_id' => $line->item_variant_id,
                    'uom_id' => $line->ordered_uom_id,
                    'requested_quantity' => $this->math->normalize($lineData->quantity),
                    'allocated_quantity' => $this->math->normalize($lineData->quantity),
                    'inventory_allocation_id' => $inventoryAllocation->getKey(),
                    'status' => SalesAllocationStatus::Active,
                ]);

                $this->orders->applyAllocated($line, $lineData->quantity, (int) $inventoryAllocation->getKey());
            }

            return $this->load($allocation);
        });
    }

    public function release(SalesAllocation $allocation, ?int $userId = null): SalesAllocation
    {
        return DB::transaction(function () use ($allocation, $userId): SalesAllocation {
            $allocation = SalesAllocation::query()->lockForUpdate()->findOrFail($allocation->getKey());
            if (! in_array($allocation->status, [SalesAllocationStatus::Active, SalesAllocationStatus::PartiallyReleased], true)) {
                throw new InvalidArgumentException('Only active sales allocations can be released.');
            }
            $allocation->load('lines.inventoryAllocation', 'lines.salesOrderLine');
            $lineIds = $allocation->lines
                ->pluck('sales_order_line_id')
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();
            $this->locks->salesOrderLines($lineIds);

            foreach ($allocation->lines as $line) {
                if (! $line instanceof SalesAllocationLine) {
                    continue;
                }
                if ($this->math->compare((string) $line->issued_quantity, '0.000000') > 0) {
                    throw new InvalidArgumentException('Issued sales allocations cannot be released.');
                }
                if ($line->inventoryAllocation instanceof InventoryAllocation) {
                    $this->inventory->release($line->inventoryAllocation, null, $userId);
                }
                if ($line->salesOrderLine instanceof SalesOrderLine) {
                    $this->releaseOrderLineAllocation($line->salesOrderLine, (string) $line->allocated_quantity);
                }
                $line->released_quantity = $this->math->add((string) $line->released_quantity, (string) $line->allocated_quantity);
                $line->status = SalesAllocationStatus::Released;
                $line->save();
            }

            $allocation->status = SalesAllocationStatus::Released;
            $allocation->released_by = $userId;
            $allocation->released_at = now();
            $allocation->save();

            return $this->load($allocation);
        });
    }

    private function releaseOrderLineAllocation(SalesOrderLine $line, string $quantity): void
    {
        $line->allocated_quantity = $this->math->sub((string) $line->allocated_quantity, $quantity);
        if ($this->math->isNegative((string) $line->allocated_quantity)) {
            $line->allocated_quantity = '0.000000';
        }
        $line->remaining_allocatable_quantity = $this->balances->remainingAllocatableForSalesOrderLine($line);
        $line->status = $this->math->compare((string) $line->allocated_quantity, '0.000000') > 0
            ? SalesOrderLineStatus::PartiallyAllocated
            : SalesOrderLineStatus::Open;
        $line->inventory_allocation_id = null;
        $line->save();
    }

    private function load(SalesAllocation $allocation): SalesAllocation
    {
        return $allocation->refresh()->load([
            'salesOrder',
            'customer',
            'warehouse',
            'warehouseLocation',
            'lines.item',
            'lines.variant',
            'lines.uom',
            'lines.inventoryAllocation',
        ]);
    }
}
