<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\StockMovement;

class StockReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StockMovement $stockMovement
    ) {}
}
