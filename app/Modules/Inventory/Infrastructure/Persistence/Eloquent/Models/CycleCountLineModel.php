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
        'tenant_id', 'cycle_count_id', 'product_id', 'variant_id',
        'system_qty', 'counted_qty', 'variance',
        'batch_number', 'lot_number', 'serial_number',
    ];

    protected $casts = [
        'system_qty'  => 'float',
        'counted_qty' => 'float',
        'variance'    => 'float',
    ];
}
