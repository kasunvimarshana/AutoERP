<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductComboItemModel extends Model
{
    use SoftDeletes;

    protected $table = 'product_combo_items';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'component_product_id',
        'quantity',
        'price_override',
        'currency',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'quantity'       => 'float',
        'price_override' => 'float',
        'sort_order'     => 'integer',
        'metadata'       => 'array',
    ];
}
