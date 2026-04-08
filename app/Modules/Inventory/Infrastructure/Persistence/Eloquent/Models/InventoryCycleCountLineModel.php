<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class InventoryCycleCountLineModel extends BaseModel
{
    use HasAudit;

    protected $table = 'inventory_cycle_count_lines';

    protected $fillable = [
        'tenant_id', 'cycle_count_id', 'product_id', 'variation_id', 'batch_id',
        'serial_number_id', 'location_id', 'expected_qty', 'counted_qty',
        'variance_qty', 'status', 'counted_at', 'counted_by', 'notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer', 'cycle_count_id' => 'integer', 'product_id' => 'integer',
        'variation_id' => 'integer', 'batch_id' => 'integer',
        'serial_number_id' => 'integer', 'location_id' => 'integer', 'counted_by' => 'integer',
        'expected_qty' => 'float', 'counted_qty' => 'float', 'variance_qty' => 'float',
        'counted_at' => 'datetime',
    ];
}
