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

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Validators\InventoryValidationService;

final class SerialTrackingService
{
    public function __construct(private readonly InventoryValidationService $validator) {}

    public function create(
        int $tenantId,
        int $itemId,
        string $serialNumber,
        ?int $organizationUnitId = null,
        ?int $itemVariantId = null,
        ?int $batchId = null,
        ?int $warehouseId = null,
        ?int $warehouseLocationId = null,
    ): InventorySerialNumber {
        $item = $this->validator->item($tenantId, $organizationUnitId, $itemId);
        $this->validator->variant($item, $itemVariantId);
        $this->validator->batch($item, $batchId, $itemVariantId);
        if ($warehouseId !== null) {
            $warehouse = $this->validator->warehouse($tenantId, $organizationUnitId, $warehouseId);
            $this->validator->location($warehouse, $warehouseLocationId);
        }

        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            throw new InvalidArgumentException('Inventory serial number is required.');
        }

        return DB::transaction(function () use (
            $tenantId,
            $organizationUnitId,
            $itemId,
            $itemVariantId,
            $serialNumber,
            $batchId,
            $warehouseId,
            $warehouseLocationId,
        ): InventorySerialNumber {
            $exists = InventorySerialNumber::query()
                ->where('tenant_id', $tenantId)
                ->where('serial_number', $serialNumber)
                ->lockForUpdate()
                ->exists();
            if ($exists) {
                throw new InvalidArgumentException('Inventory serial number already exists for this tenant.');
            }

            return InventorySerialNumber::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'item_id' => $itemId,
                'item_variant_id' => $itemVariantId,
                'serial_number' => $serialNumber,
                'batch_id' => $batchId,
                'warehouse_id' => $warehouseId,
                'warehouse_location_id' => $warehouseLocationId,
                'status' => SerialStatus::Available,
            ]);
        }, 3);
    }
}
