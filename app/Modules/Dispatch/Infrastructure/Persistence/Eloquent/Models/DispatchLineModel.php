<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class DispatchLineModel extends BaseModel
{
    use HasAudit;

    protected $table = 'dispatch_lines';

    protected $fillable = [
        'tenant_id', 'dispatch_id', 'sales_order_line_id', 'product_id', 'product_variant_id',
        'description', 'quantity', 'unit_of_measure', 'warehouse_location_id',
        'batch_number', 'serial_number', 'status', 'weight', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'           => 'integer',
        'dispatch_id'         => 'integer',
        'sales_order_line_id' => 'integer',
        'product_id'          => 'integer',
        'product_variant_id'  => 'integer',
        'quantity'            => 'float',
        'warehouse_location_id' => 'integer',
        'weight'              => 'float',
        'metadata'            => 'array',
    ];
}
