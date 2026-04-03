<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class StockReturnLineModel extends BaseModel
{
    use HasAudit;

    protected $table = 'stock_return_lines';

    protected $fillable = [
        'tenant_id', 'stock_return_id', 'product_id', 'variation_id', 'batch_id',
        'serial_number_id', 'uom_id', 'quantity_requested', 'quantity_approved',
        'unit_price', 'unit_cost', 'condition', 'disposition', 'quality_check_status',
        'quality_checked_by', 'quality_checked_at', 'notes',
    ];

    protected $casts = [
        'tenant_id'          => 'integer',
        'stock_return_id'    => 'integer',
        'product_id'         => 'integer',
        'variation_id'       => 'integer',
        'batch_id'           => 'integer',
        'serial_number_id'   => 'integer',
        'uom_id'             => 'integer',
        'quantity_requested' => 'float',
        'quantity_approved'  => 'float',
        'unit_price'         => 'float',
        'unit_cost'          => 'float',
        'quality_checked_by' => 'integer',
        'quality_checked_at' => 'datetime',
    ];
}
