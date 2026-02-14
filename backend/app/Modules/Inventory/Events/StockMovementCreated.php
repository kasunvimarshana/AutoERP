<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockMovementCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StockMovement $stockMovement;

    public function __construct(StockMovement $stockMovement)
    {
        $this->stockMovement = $stockMovement;
    }
}
