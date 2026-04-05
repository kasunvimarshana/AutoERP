<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class InventoryAdjusted extends BaseEvent
{
    public int $productId;
    public int $warehouseId;
    public float $difference;

    public function __construct(int $tenantId, int $productId, int $warehouseId, float $difference)
    {
        parent::__construct($tenantId);
        $this->productId   = $productId;
        $this->warehouseId = $warehouseId;
        $this->difference  = $difference;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'productId' => $this->productId,
            'warehouseId' => $this->warehouseId,
            'difference' => $this->difference,
        ]);
    }
}
