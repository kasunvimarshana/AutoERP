<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class GrnLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'grn_lines';

    protected $fillable = [
        'tenant_id',
        'grn_header_id',
        'purchase_order_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'location_id',
        'uom_id',
        'expected_qty',
        'received_qty',
        'rejected_qty',
        'unit_cost',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'grn_header_id' => 'integer',
        'purchase_order_line_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'location_id' => 'integer',
        'uom_id' => 'integer',
        'expected_qty' => 'decimal:6',
        'received_qty' => 'decimal:6',
        'rejected_qty' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'line_cost' => 'decimal:6',
    ];
}
