<?php

declare(strict_types=1);

namespace Modules\Pos\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class PosOrderLineModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'pos_order_lines';

    protected $fillable = [
        'tenant_id',
        'pos_order_id',
        'product_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'line_total',
    ];
}
