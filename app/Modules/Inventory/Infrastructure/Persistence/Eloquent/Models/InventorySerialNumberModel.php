<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class InventorySerialNumberModel extends BaseModel
{
    use HasAudit;

    protected $table = 'inventory_serial_numbers';

    protected $fillable = [
        'tenant_id', 'product_id', 'variation_id', 'batch_id', 'serial_number',
        'location_id', 'status', 'purchase_price', 'currency',
        'purchased_at', 'sold_at', 'returned_at', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'product_id' => 'integer', 'variation_id' => 'integer',
        'batch_id' => 'integer', 'location_id' => 'integer', 'purchase_price' => 'float',
        'purchased_at' => 'datetime', 'sold_at' => 'datetime', 'returned_at' => 'datetime',
        'metadata' => 'array',
    ];
}
