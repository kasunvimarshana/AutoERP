<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class StockLevelModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'stock_levels';

    protected $fillable = [
        'tenant_id', 'product_id', 'variant_id', 'warehouse_id', 'location_id',
        'batch_number', 'lot_number', 'serial_number',
        'quantity', 'reserved_quantity', 'expiry_date',
    ];

    protected $casts = [
        'quantity'          => 'float',
        'reserved_quantity' => 'float',
        'expiry_date'       => 'date',
    ];
}
