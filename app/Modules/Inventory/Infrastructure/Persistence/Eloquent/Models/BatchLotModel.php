<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class BatchLotModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'batch_lots';

    protected $fillable = [
        'tenant_id', 'product_id', 'variant_id', 'batch_number', 'lot_number',
        'serial_number', 'manufacture_date', 'expiry_date', 'quantity', 'unit_cost',
        'status', 'supplier_id', 'metadata',
    ];

    protected $casts = [
        'quantity'         => 'decimal:4',
        'unit_cost'        => 'decimal:4',
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
        'metadata'         => 'array',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];
}
