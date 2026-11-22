<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Inventory\Enums\BatchStatus;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Enums\TrackingType;

final class BatchTrackingService
{
    public function __construct(private readonly InventoryValidationService $validator) {}

    public function create(
        int $tenantId,
        int $itemId,
        string $batchNumber,
        ?int $organizationUnitId = null,
        ?int $itemVariantId = null,
        ?string $lotNumber = null,
        ?string $manufactureDate = null,
        ?string $expiryDate = null,
    ): InventoryBatch {
        $item = $this->validator->item($tenantId, $organizationUnitId, $itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $itemVariantId);

        $trackingType = $item->tracking_type instanceof TrackingType
            ? $item->tracking_type
            : TrackingType::from((string) $item->tracking_type);
        if (! in_array($trackingType, [TrackingType::Batch, TrackingType::Lot, TrackingType::Serial], true)) {
            throw ValidationException::withMessages([
                'item_id' => ['Only batch, lot or serial tracked stock items can own inventory batches.'],
            ]);
        }

        $batchNumber = trim($batchNumber);
        $lotNumber = $lotNumber === null || trim($lotNumber) === '' ? null : trim($lotNumber);
        if ($batchNumber === '') {
            throw new InvalidArgumentException('Inventory batch number is required.');
        }

        if ($manufactureDate !== null && $expiryDate !== null) {
            $manufacture = CarbonImmutable::parse($manufactureDate);
            $expiry = CarbonImmutable::parse($expiryDate);
            if ($expiry->lessThan($manufacture)) {
                throw ValidationException::withMessages([
                    'expiry_date' => ['The expiry date cannot precede the manufacture date.'],
                ]);
            }
        }

        return DB::transaction(function () use ($tenantId, $organizationUnitId, $itemId, $itemVariantId, $batchNumber, $lotNumber, $manufactureDate, $expiryDate): InventoryBatch {
            $duplicate = InventoryBatch::query()
                ->where('tenant_id', $tenantId)
                ->where('item_id', $itemId)
                ->where('batch_number', $batchNumber)
                ->lockForUpdate()
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'batch_number' => ['This batch number already exists for the selected item.'],
                ]);
            }

            return InventoryBatch::query()->create([
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'item_id' => $itemId,
                'item_variant_id' => $itemVariantId,
                'batch_number' => $batchNumber,
                'lot_number' => $lotNumber,
                'manufacture_date' => $manufactureDate,
                'expiry_date' => $expiryDate,
                'status' => BatchStatus::Active,
            ])->load(['item', 'variant']);
        }, 3);
    }
}
