<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class InventoryMovementModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'tenant_id', 'product_id', 'variant_id', 'warehouse_id', 'location_id',
        'type', 'reference_type', 'reference_id',
        'quantity', 'unit_cost', 'total_cost', 'unit_of_measure',
        'quantity_before', 'quantity_after', 'notes', 'created_by',
    ];

    protected $casts = [
        'quantity'        => 'decimal:4',
        'unit_cost'       => 'decimal:4',
        'total_cost'      => 'decimal:4',
        'quantity_before' => 'decimal:4',
        'quantity_after'  => 'decimal:4',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];
}
