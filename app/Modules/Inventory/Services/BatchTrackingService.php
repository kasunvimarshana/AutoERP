<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use InvalidArgumentException;
use Modules\Inventory\Enums\BatchStatus;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Validators\InventoryValidationService;

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
    ): InventoryBatch {
        $item = $this->validator->item($tenantId, $organizationUnitId, $itemId);
        $this->validator->variant($item, $itemVariantId);

        if (trim($batchNumber) === '') {
            throw new InvalidArgumentException('Inventory batch number is required.');
        }

        return InventoryBatch::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'item_id' => $itemId,
            'item_variant_id' => $itemVariantId,
            'batch_number' => $batchNumber,
            'lot_number' => $lotNumber,
            'status' => BatchStatus::Active,
        ]);
    }
}
