<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockAvailabilityResult;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Validators\InventoryValidationService;

final class InventoryAvailabilityService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly StockBalanceService $balances,
        private readonly InventoryValidationService $validator,
    ) {}

    public function availability(StockBalanceData $data): StockAvailabilityResult
    {
        $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $data->itemId);
        $this->validator->assertStockable($item);
        $this->validator->variant($item, $data->itemVariantId);
        $warehouse = $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        $this->validator->location($warehouse, $data->warehouseLocationId);
        if ($data->batchId !== null) {
            $this->validator->batch($item, $data->batchId, $data->itemVariantId);
        }

        $balance = $this->balances->find($data);
        if ($balance !== null) {
            $this->balances->assertReconciled($balance);
        }
        $quantityOnHand = (string) ($balance?->quantity_on_hand ?? '0.000000');
        $quantityInTransit = (string) ($balance?->quantity_in_transit ?? '0.000000');

        return new StockAvailabilityResult(
            itemId: $data->itemId,
            warehouseId: $data->warehouseId,
            quantityOnHand: $quantityOnHand,
            quantityReserved: (string) ($balance?->quantity_reserved ?? '0.000000'),
            quantityAllocated: (string) ($balance?->quantity_allocated ?? '0.000000'),
            quantityAvailable: (string) ($balance?->quantity_available ?? '0.000000'),
            quantityInTransit: $quantityInTransit,
            quantityReturned: (string) ($balance?->quantity_returned ?? '0.000000'),
            quantityDamaged: (string) ($balance?->quantity_damaged ?? '0.000000'),
            quantityQuarantine: (string) ($balance?->quantity_quarantine ?? '0.000000'),
            quantityExpired: (string) ($balance?->quantity_expired ?? '0.000000'),
            quantityScrapped: (string) ($balance?->quantity_scrapped ?? '0.000000'),
            quantityTotal: $this->math->add($quantityOnHand, $quantityInTransit),
            baseUomId: $balance?->base_uom_id ?? $item->base_uom_id,
            itemVariantId: $data->itemVariantId,
            warehouseLocationId: $data->warehouseLocationId,
            batchId: $data->batchId,
        );
    }
}
