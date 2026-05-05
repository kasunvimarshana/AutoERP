<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class StockReorderRuleModel extends Model
{
    use HasTenant;

    protected $table = 'stock_reorder_rules';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'minimum_quantity',
        'maximum_quantity',
        'reorder_quantity',
        'is_active',
    ];

    protected $casts = [
        'tenant_id'   => 'integer',
        'product_id'  => 'integer',
        'variant_id'  => 'integer',
        'warehouse_id'=> 'integer',
        'is_active'   => 'boolean',
    ];
}
