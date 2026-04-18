<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductModel extends Model
{
    use HasAudit;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'brand_id',
        'org_unit_id',
        'type',
        'name',
        'slug',
        'sku',
        'description',
        'base_uom_id',
        'purchase_uom_id',
        'sales_uom_id',
        'uom_conversion_factor',
        'is_batch_tracked',
        'is_lot_tracked',
        'is_serial_tracked',
        'valuation_method',
        'standard_cost',
        'income_account_id',
        'cogs_account_id',
        'inventory_account_id',
        'expense_account_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_batch_tracked' => 'boolean',
        'is_lot_tracked' => 'boolean',
        'is_serial_tracked' => 'boolean',
        'is_active' => 'boolean',
        'uom_conversion_factor' => 'decimal:10',
        'standard_cost' => 'decimal:4',
    ];
}
