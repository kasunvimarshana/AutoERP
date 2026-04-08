<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class InventoryLocationModel extends BaseModel
{
    use HasAudit;

    protected $table = 'inventory_locations';

    protected $fillable = [
        'tenant_id', 'warehouse_id', 'zone_id', 'code', 'name', 'type',
        'aisle', 'row', 'level', 'bin', 'capacity', 'weight_limit',
        'barcode', 'qr_code', 'is_pickable', 'is_storable', 'is_packing',
        'is_active', 'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'warehouse_id' => 'integer', 'zone_id' => 'integer',
        'capacity' => 'float', 'weight_limit' => 'float',
        'is_pickable' => 'boolean', 'is_storable' => 'boolean',
        'is_packing' => 'boolean', 'is_active' => 'boolean', 'metadata' => 'array',
    ];
}
