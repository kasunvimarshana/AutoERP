<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Inventory\DTOs\StockAvailabilityResult;
use Modules\Inventory\DTOs\StockBalanceData;

final class StockAvailabilityService
{
    public function __construct(private readonly InventoryAvailabilityService $availability) {}

    public function availability(StockBalanceData $data): StockAvailabilityResult
    {
        return $this->availability->availability($data);
    }
}
