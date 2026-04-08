<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class InventoryAdjustmentLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'inventory_adjustment_lines';

    protected $fillable = [
        'tenant_id', 'adjustment_id', 'product_id', 'variant_id', 'location_id',
        'quantity_system', 'quantity_counted', 'quantity_difference', 'unit_cost', 'unit_of_measure', 'notes',
    ];

    protected $casts = [
        'quantity_system'     => 'decimal:4',
        'quantity_counted'    => 'decimal:4',
        'quantity_difference' => 'decimal:4',
        'unit_cost'           => 'decimal:4',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];
}
