<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class StockMovementModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'stock_movements';

    protected $fillable = [
        'tenant_id', 'product_id', 'variant_id', 'warehouse_id', 'location_id',
        'type', 'quantity', 'batch_number', 'lot_number', 'serial_number',
        'reference_type', 'reference_id', 'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];
}
