<?php

namespace App\Events;

use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockMovementRecorded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StockLedger $stockLedger
    ) {}
}
