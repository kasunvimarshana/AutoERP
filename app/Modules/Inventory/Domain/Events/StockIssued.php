<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class StockIssued extends BaseEvent
{
    public int $productId;
    public int $warehouseId;
    public float $quantity;

    public function __construct(int $tenantId, int $productId, int $warehouseId, float $quantity)
    {
        parent::__construct($tenantId);
        $this->productId   = $productId;
        $this->warehouseId = $warehouseId;
        $this->quantity    = $quantity;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'productId' => $this->productId,
            'warehouseId' => $this->warehouseId,
            'quantity' => $this->quantity,
        ]);
    }
}
