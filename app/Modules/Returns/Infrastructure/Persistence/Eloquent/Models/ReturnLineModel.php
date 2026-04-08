<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ReturnLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'return_lines';

    protected $fillable = [
        'tenant_id',
        'return_type',
        'return_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'line_total',
        'batch_number',
        'lot_number',
        'serial_number',
        'condition',
        'restockable',
        'quality_notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'line_total' => 'float',
        'restockable' => 'boolean',
    ];
}
