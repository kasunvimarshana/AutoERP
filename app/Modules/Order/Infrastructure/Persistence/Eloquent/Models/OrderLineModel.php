<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class OrderLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'order_lines';

    protected $fillable = [
        'tenant_id', 'order_type', 'order_id', 'product_id', 'variant_id',
        'description', 'quantity', 'unit_price', 'discount', 'tax_rate', 'line_total',
    ];

    protected $casts = [
        'quantity'   => 'float',
        'unit_price' => 'float',
        'discount'   => 'float',
        'tax_rate'   => 'float',
        'line_total' => 'float',
    ];
}
