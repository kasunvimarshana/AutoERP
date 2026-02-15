<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockMovementRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly StockLedger $stockLedger,
        public readonly int $tenantId,
        public readonly int $userId
    ) {}
}
