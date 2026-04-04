<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductUomSettingModel extends BaseModel
{
    protected $table = 'product_uom_settings';

    protected $casts = [
        'purchase_factor'  => 'float',
        'sales_factor'     => 'float',
        'inventory_factor' => 'float',
    ];
}
