<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ProductSupplierPriceModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'product_supplier_prices';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'supplier_id',
        'currency_id',
        'uom_id',
        'min_order_quantity',
        'unit_price',
        'discount_percent',
        'lead_time_days',
        'is_preferred',
        'is_active',
        'effective_from',
        'effective_to',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'supplier_id' => 'integer',
        'currency_id' => 'integer',
        'uom_id' => 'integer',
        'min_order_quantity' => 'string',
        'unit_price' => 'string',
        'discount_percent' => 'string',
        'lead_time_days' => 'integer',
        'is_preferred' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'metadata' => 'array',
    ];
}
