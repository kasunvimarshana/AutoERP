<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class PurchaseOrderLineModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'tenant_id', 'purchase_order_id', 'product_id', 'variant_id', 'sku', 'product_name',
        'unit_of_measure', 'quantity_ordered', 'quantity_received', 'quantity_returned',
        'unit_cost', 'discount_percent', 'discount_amount', 'tax_rate', 'tax_amount',
        'line_total', 'notes',
    ];

    protected $casts = [
        'quantity_ordered'  => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'quantity_returned' => 'decimal:4',
        'unit_cost'         => 'decimal:4',
        'discount_percent'  => 'decimal:4',
        'discount_amount'   => 'decimal:4',
        'tax_rate'          => 'decimal:4',
        'tax_amount'        => 'decimal:4',
        'line_total'        => 'decimal:4',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];
}
