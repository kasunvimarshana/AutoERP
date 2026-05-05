<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderLineModel extends Model
{
    protected $table = 'sales_order_lines';

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'product_id',
        'variant_id',
        'description',
        'uom_id',
        'ordered_qty',
        'shipped_qty',
        'reserved_qty',
        'unit_price',
        'discount_pct',
        'tax_group_id',
        'line_total',
        'income_account_id',
        'batch_id',
        'serial_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'sales_order_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'uom_id' => 'integer',
        'tax_group_id' => 'integer',
        'income_account_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'ordered_qty' => 'decimal:6',
        'shipped_qty' => 'decimal:6',
        'reserved_qty' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_pct' => 'decimal:6',
        'line_total' => 'decimal:6',
    ];
}
