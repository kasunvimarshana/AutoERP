<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert
{
    use Dispatchable, SerializesModels;

    public function __construct(public Inventory $inventory)
    {
    }
}
