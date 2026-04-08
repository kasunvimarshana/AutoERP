<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PurchaseOrderLineModel extends BaseModel
{
    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'tenant_id', 'purchase_order_id', 'line_number', 'product_id', 'variation_id',
        'description', 'uom_id', 'quantity_ordered', 'quantity_received', 'unit_price',
        'discount_percent', 'tax_percent', 'line_total', 'expected_date', 'notes',
        'metadata', 'status',
    ];

    protected $casts = [
        'tenant_id'         => 'integer',
        'purchase_order_id' => 'integer',
        'line_number'       => 'integer',
        'product_id'        => 'integer',
        'variation_id'      => 'integer',
        'uom_id'            => 'integer',
        'quantity_ordered'  => 'float',
        'quantity_received' => 'float',
        'unit_price'        => 'float',
        'discount_percent'  => 'float',
        'tax_percent'       => 'float',
        'line_total'        => 'float',
        'metadata'          => 'array',
    ];
}
