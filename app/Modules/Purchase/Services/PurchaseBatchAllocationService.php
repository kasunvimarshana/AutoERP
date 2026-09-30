<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Services\BatchTrackingService;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Purchase\DTOs\GoodsReceiptBatchAllocationData;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\GoodsReceiptNoteLineBatchAllocation;

final class PurchaseBatchAllocationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $inventoryValidator,
        private readonly BatchTrackingService $batches,
    ) {}

    /** @param list<GoodsReceiptBatchAllocationData> $allocations */
    public function assertValid(Item $item, string $acceptedQuantity, array $allocations, string $field = 'batch_allocations'): void
    {
        $tracking = $item->tracking_type instanceof TrackingType
            ? $item->tracking_type
            : TrackingType::from((string) $item->tracking_type);
        $requiresBatch = in_array($tracking, [TrackingType::Batch, TrackingType::Lot], true);

        if (! $requiresBatch && $allocations !== []) {
            throw ValidationException::withMessages([
                $field => ['Batch allocations are only allowed for batch or lot tracked items.'],
            ]);
        }
        if ($requiresBatch && $this->math->compare($acceptedQuantity, '0.000000') > 0 && $allocations === []) {
            throw ValidationException::withMessages([
                $field => ['Batch or lot tracked receipt lines require batch allocations.'],
            ]);
        }
        if (! $requiresBatch) {
            return;
        }

        $total = '0.000000';
        foreach ($allocations as $allocation) {
            $this->inventoryValidator->assertPositiveQuantity($allocation->quantity);
            $total = $this->math->add($total, $allocation->quantity);
        }
        if ($this->math->compare($total, $acceptedQuantity) !== 0) {
            throw ValidationException::withMessages([
                $field => ['Batch allocation quantities must equal the accepted receipt quantity.'],
            ]);
        }
    }

    /**
     * @param  list<GoodsReceiptBatchAllocationData>  $allocations
     * @return Collection<int, GoodsReceiptNoteLineBatchAllocation>
     */
    public function store(GoodsReceiptNoteLine $line, array $allocations): Collection
    {
        if ($allocations === []) {
            return collect();
        }

        $line->loadMissing('item');
        $stored = collect();
        foreach ($allocations as $allocation) {
            $batch = $allocation->batchId === null
                ? $this->batches->create(
                    tenantId: (int) $line->tenant_id,
                    itemId: (int) $line->item_id,
                    batchNumber: (string) $allocation->batchNumber,
                    organizationUnitId: $line->organization_unit_id === null ? null : (int) $line->organization_unit_id,
                    itemVariantId: $line->item_variant_id === null ? null : (int) $line->item_variant_id,
                    lotNumber: $allocation->lotNumber,
                    manufactureDate: $allocation->manufactureDate,
                    expiryDate: $allocation->expiryDate,
                )
                : $this->existingBatch($line, $allocation->batchId);

            $stored->push($line->batchAllocations()->create([
                'tenant_id' => $line->tenant_id,
                'organization_unit_id' => $line->organization_unit_id,
                'batch_id' => $batch->getKey(),
                'quantity' => $this->math->normalize($allocation->quantity),
                'base_quantity' => $this->math->mul((string) $allocation->quantity, (string) $line->uom_conversion_factor),
            ]));
        }

        return $stored;
    }

    private function existingBatch(GoodsReceiptNoteLine $line, int $batchId): InventoryBatch
    {
        return $this->inventoryValidator->batch(
            $line->item,
            $batchId,
            $line->item_variant_id === null ? null : (int) $line->item_variant_id,
        );
    }
}
