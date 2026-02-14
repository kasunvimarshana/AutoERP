<?php

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\SKU;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SKUCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SKU $sku;

    public function __construct(SKU $sku)
    {
        $this->sku = $sku;
    }
}
