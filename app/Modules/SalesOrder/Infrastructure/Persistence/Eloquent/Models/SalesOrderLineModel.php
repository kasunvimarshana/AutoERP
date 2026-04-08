<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SalesOrderLineModel extends BaseModel
{
    protected $table = 'sales_order_lines';

    protected $fillable = [
        'tenant_id', 'sales_order_id', 'product_id', 'product_variant_id', 'description',
        'quantity', 'unit_price', 'tax_rate', 'discount_amount', 'total_amount',
        'unit_of_measure', 'status', 'warehouse_location_id', 'batch_number',
        'serial_number', 'notes', 'metadata',
    ];

    protected $casts = [
        'tenant_id'            => 'integer',
        'sales_order_id'       => 'integer',
        'product_id'           => 'integer',
        'product_variant_id'   => 'integer',
        'warehouse_location_id' => 'integer',
        'quantity'             => 'float',
        'unit_price'           => 'float',
        'tax_rate'             => 'float',
        'discount_amount'      => 'float',
        'total_amount'         => 'float',
        'metadata'             => 'array',
    ];
}
