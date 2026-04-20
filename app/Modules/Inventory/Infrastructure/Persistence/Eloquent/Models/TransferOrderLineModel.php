<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class TransferOrderLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'transfer_order_lines';

    protected $fillable = [
        'tenant_id',
        'transfer_order_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'from_location_id',
        'to_location_id',
        'uom_id',
        'requested_qty',
        'shipped_qty',
        'received_qty',
        'unit_cost',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'transfer_order_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'from_location_id' => 'integer',
        'to_location_id' => 'integer',
        'uom_id' => 'integer',
    ];
}
