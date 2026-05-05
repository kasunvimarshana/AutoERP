<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnLineModel extends Model
{
    protected $table = 'sales_return_lines';

    protected $fillable = [
        'tenant_id',
        'sales_return_id',
        'original_sales_order_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'to_location_id',
        'uom_id',
        'return_qty',
        'unit_price',
        'line_total',
        'condition',
        'disposition',
        'restocking_fee',
        'quality_check_notes',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'sales_return_id' => 'integer',
        'original_sales_order_line_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'to_location_id' => 'integer',
        'uom_id' => 'integer',
        'return_qty' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'line_total' => 'decimal:6',
        'restocking_fee' => 'decimal:6',
    ];
}
