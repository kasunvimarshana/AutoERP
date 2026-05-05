<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class StockMovementModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'stock_movements';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'from_location_id',
        'to_location_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'uom_id',
        'quantity',
        'unit_cost',
        'performed_by',
        'performed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'from_location_id' => 'integer',
        'to_location_id' => 'integer',
        'reference_id' => 'integer',
        'uom_id' => 'integer',
        'performed_by' => 'integer',
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
