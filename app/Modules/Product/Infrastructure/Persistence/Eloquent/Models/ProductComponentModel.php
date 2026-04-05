<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ProductComponentModel extends BaseModel
{
    protected $table = 'product_components';

    protected $fillable = [
        'tenant_id', 'parent_product_id', 'component_product_id',
        'quantity', 'unit', 'is_optional',
    ];

    protected $casts = [
        'id'                   => 'int',
        'tenant_id'            => 'int',
        'parent_product_id'    => 'int',
        'component_product_id' => 'int',
        'quantity'             => 'float',
        'is_optional'          => 'bool',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'deleted_at'           => 'datetime',
    ];
}
