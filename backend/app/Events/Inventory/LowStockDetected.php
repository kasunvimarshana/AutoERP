<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $productSku,
        public readonly float $currentQuantity,
        public readonly float $minimumQuantity,
        public readonly int $locationId,
        public readonly int $tenantId
    ) {}
}
