<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class StockExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $productSku,
        public readonly string $batchNumber,
        public readonly float $quantity,
        public readonly Carbon $expiryDate,
        public readonly int $daysUntilExpiry,
        public readonly int $locationId,
        public readonly int $tenantId
    ) {}
}
