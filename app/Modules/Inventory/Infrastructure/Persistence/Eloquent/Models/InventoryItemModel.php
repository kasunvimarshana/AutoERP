<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class InventoryItemModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'inventory_items';

    protected $fillable = [
        'tenant_id', 'product_id', 'variant_id', 'warehouse_id', 'location_id',
        'quantity_on_hand', 'quantity_reserved', 'quantity_in_transit',
        'quantity_available', 'average_cost', 'unit_of_measure',
    ];

    protected $casts = [
        'quantity_on_hand'    => 'decimal:4',
        'quantity_reserved'   => 'decimal:4',
        'quantity_in_transit' => 'decimal:4',
        'quantity_available'  => 'decimal:4',
        'average_cost'        => 'decimal:4',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];
}
