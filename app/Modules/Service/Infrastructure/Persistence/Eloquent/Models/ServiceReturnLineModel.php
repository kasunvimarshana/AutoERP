<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceReturnLineModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_return_lines';

    protected $fillable = [
        'tenant_id',
        'service_return_id',
        'service_part_id',
        'product_id',
        'description',
        'quantity',
        'uom_id',
        'unit_amount',
        'line_amount',
        'stock_reference_type',
        'stock_reference_id',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'service_return_id' => 'integer',
        'service_part_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:6',
        'uom_id' => 'integer',
        'unit_amount' => 'decimal:6',
        'line_amount' => 'decimal:6',
        'stock_reference_id' => 'integer',
        'metadata' => 'array',
    ];
}
