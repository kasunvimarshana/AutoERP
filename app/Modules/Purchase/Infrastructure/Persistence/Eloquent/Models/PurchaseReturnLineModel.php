<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PurchaseReturnLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'purchase_return_lines';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'purchase_return_id',
        'original_grn_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'from_location_id',
        'uom_id',
        'return_qty',
        'unit_cost',
        'condition',
        'disposition',
        'restocking_fee',
        'quality_check_notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'purchase_return_id' => 'integer',
        'original_grn_line_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'from_location_id' => 'integer',
        'uom_id' => 'integer',
        'return_qty' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'restocking_fee' => 'decimal:6',
        'line_cost' => 'decimal:6',
    ];
}
