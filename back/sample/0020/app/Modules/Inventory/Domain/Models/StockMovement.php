<?php

namespace App\Modules\Inventory\Domain\Models;

use App\Modules\Core\Domain\Models\BaseModel;

class StockMovement extends BaseModel {
    protected $fillable = [
        'tenant_id', 'product_id', 'warehouse_location_id', 
        'quantity', 'type', 'batch_id', 'serial_id', 
        'reference_type', 'reference_id'
    ];
    
    // Logic to prevent updates could be added here in a real scenario
}
