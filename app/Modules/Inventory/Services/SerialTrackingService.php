<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

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
        $this->validator->batch($item, $batchId);

        if (trim($serialNumber) === '') {
            throw new InvalidArgumentException('Inventory serial number is required.');
        }

        if (InventorySerialNumber::query()->where('tenant_id', $tenantId)->where('serial_number', $serialNumber)->exists()) {
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
    }
}
