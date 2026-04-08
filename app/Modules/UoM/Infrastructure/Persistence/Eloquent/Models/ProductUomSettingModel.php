<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductUomSettingModel extends BaseModel
{
    protected $table = 'product_uom_settings';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'base_uom_id',
        'purchase_uom_id',
        'sales_uom_id',
        'inventory_uom_id',
        'purchase_factor',
        'sales_factor',
        'inventory_factor',
        'is_active',
    ];

    protected $casts = [
        'tenant_id'       => 'integer',
        'product_id'      => 'integer',
        'base_uom_id'     => 'integer',
        'purchase_uom_id' => 'integer',
        'sales_uom_id'    => 'integer',
        'inventory_uom_id' => 'integer',
        'purchase_factor' => 'float',
        'sales_factor'    => 'float',
        'inventory_factor'=> 'float',
        'is_active'       => 'boolean',
    ];
}
