<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class StockMovementModel extends BaseModel
{
    use HasAudit;

    protected $table = 'stock_movements';

    protected $fillable = [
        'tenant_id', 'reference_number', 'movement_type', 'status',
        'product_id', 'variation_id', 'from_location_id', 'to_location_id',
        'batch_id', 'serial_number_id', 'uom_id', 'quantity', 'unit_cost',
        'currency', 'reference_type', 'reference_id', 'performed_by',
        'movement_date', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'        => 'integer',
        'product_id'       => 'integer',
        'variation_id'     => 'integer',
        'from_location_id' => 'integer',
        'to_location_id'   => 'integer',
        'batch_id'         => 'integer',
        'serial_number_id' => 'integer',
        'uom_id'           => 'integer',
        'reference_id'     => 'integer',
        'performed_by'     => 'integer',
        'quantity'         => 'float',
        'unit_cost'        => 'float',
        'movement_date'    => 'datetime',
        'metadata'         => 'array',
    ];
}
