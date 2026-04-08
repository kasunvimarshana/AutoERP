<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class CycleCountLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'cycle_count_lines';

    protected $fillable = [
        'tenant_id', 'cycle_count_id', 'product_id', 'variant_id', 'batch_lot_id',
        'system_quantity', 'counted_quantity', 'variance', 'status', 'notes',
    ];

    protected $casts = [
        'system_quantity'  => 'decimal:4',
        'counted_quantity' => 'decimal:4',
        'variance'         => 'decimal:4',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];
}
