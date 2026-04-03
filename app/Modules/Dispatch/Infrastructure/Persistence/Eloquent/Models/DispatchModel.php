<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class DispatchModel extends BaseModel
{
    use HasAudit;

    protected $table = 'dispatches';

    protected $fillable = [
        'tenant_id', 'reference_number', 'status', 'warehouse_id', 'sales_order_id',
        'customer_id', 'customer_reference', 'dispatch_date', 'estimated_delivery_date',
        'actual_delivery_date', 'carrier', 'tracking_number', 'currency', 'total_weight',
        'notes', 'metadata', 'confirmed_by', 'confirmed_at', 'shipped_by', 'shipped_at',
    ];

    protected $casts = [
        'tenant_id'    => 'integer',
        'warehouse_id' => 'integer',
        'sales_order_id' => 'integer',
        'customer_id'  => 'integer',
        'dispatch_date'          => 'date',
        'estimated_delivery_date'=> 'date',
        'actual_delivery_date'   => 'date',
        'total_weight'  => 'float',
        'confirmed_by'  => 'integer',
        'confirmed_at'  => 'datetime',
        'shipped_by'    => 'integer',
        'shipped_at'    => 'datetime',
        'metadata'      => 'array',
    ];
}
