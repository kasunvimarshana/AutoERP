<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServicePartModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_parts';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'service_work_order_id',
        'service_task_id',
        'product_id',
        'part_source',
        'description',
        'quantity',
        'uom_id',
        'unit_cost',
        'unit_price',
        'line_amount',
        'is_returned',
        'is_warranty_covered',
        'stock_reference_type',
        'stock_reference_id',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'service_work_order_id' => 'integer',
        'service_task_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:6',
        'uom_id' => 'integer',
        'unit_cost' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'line_amount' => 'decimal:6',
        'is_returned' => 'boolean',
        'is_warranty_covered' => 'boolean',
        'stock_reference_id' => 'integer',
        'metadata' => 'array',
    ];
}
