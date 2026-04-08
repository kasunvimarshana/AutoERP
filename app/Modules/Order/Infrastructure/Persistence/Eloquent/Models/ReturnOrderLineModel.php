<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ReturnOrderLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'return_order_lines';

    protected $fillable = [
        'tenant_id', 'return_order_id', 'product_id', 'variant_id', 'sku', 'product_name',
        'unit_of_measure', 'quantity_returned', 'quantity_restocked',
        'unit_price', 'line_total', 'condition', 'is_restockable',
        'batch_lot_id', 'quality_check_status', 'notes',
    ];

    protected $casts = [
        'quantity_returned'  => 'decimal:4',
        'quantity_restocked' => 'decimal:4',
        'unit_price'         => 'decimal:4',
        'line_total'         => 'decimal:4',
        'is_restockable'     => 'boolean',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
    ];
}
