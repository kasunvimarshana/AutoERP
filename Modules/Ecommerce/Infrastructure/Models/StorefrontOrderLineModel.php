<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorefrontOrderLineModel extends Model
{
    use SoftDeletes;

    protected $table = 'storefront_order_lines';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'product_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'line_total',
    ];
}
