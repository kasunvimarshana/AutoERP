<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Inventory\DTOs\StockAvailabilityResult;
use Modules\Inventory\DTOs\StockBalanceData;

final class StockAvailabilityService
{
    public function __construct(private readonly StockBalanceService $balances) {}

    public function availability(StockBalanceData $data): StockAvailabilityResult
    {
        $balance = $this->balances->getOrCreate($data);

        return new StockAvailabilityResult(
            itemId: $data->itemId,
            warehouseId: $data->warehouseId,
            quantityOnHand: (string) $balance->quantity_on_hand,
            quantityReserved: (string) $balance->quantity_reserved,
            quantityAllocated: (string) $balance->quantity_allocated,
            quantityAvailable: (string) $balance->quantity_available,
            itemVariantId: $data->itemVariantId,
            warehouseLocationId: $data->warehouseLocationId,
            batchId: $data->batchId,
        );
    }
}
