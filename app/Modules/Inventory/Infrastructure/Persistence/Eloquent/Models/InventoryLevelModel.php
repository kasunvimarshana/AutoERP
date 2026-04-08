<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class InventoryLevelModel extends BaseModel
{
    use HasAudit;

    protected $table = 'inventory_levels';

    protected $fillable = [
        'tenant_id', 'product_id', 'variation_id', 'location_id', 'batch_id', 'uom_id',
        'qty_on_hand', 'qty_reserved', 'qty_available', 'qty_on_order',
        'reorder_point', 'reorder_qty', 'max_qty', 'min_qty', 'last_counted_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'product_id' => 'integer', 'variation_id' => 'integer',
        'location_id' => 'integer', 'batch_id' => 'integer', 'uom_id' => 'integer',
        'qty_on_hand' => 'float', 'qty_reserved' => 'float', 'qty_available' => 'float',
        'qty_on_order' => 'float', 'reorder_point' => 'float', 'reorder_qty' => 'float',
        'max_qty' => 'float', 'min_qty' => 'float', 'last_counted_at' => 'datetime',
    ];
}
