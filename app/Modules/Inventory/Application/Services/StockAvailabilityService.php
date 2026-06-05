<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Support\InventoryServiceSupport;

final class StockAvailabilityService
{
    public function __construct(private readonly InventoryServiceSupport $support) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @return array{on_hand_quantity: float, reserved_quantity: float, available_quantity: float}
     */
    public function check(array $criteria): array
    {
        return $this->support->availability($criteria);
    }
}
