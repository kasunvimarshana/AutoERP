<?php

declare(strict_types=1);

namespace Modules\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Inventory\Models\StockCount;

class StockCountCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StockCount $stockCount
    ) {}
}
