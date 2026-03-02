<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLineModel extends Model
{
    protected $table = 'sales_order_lines';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'string',
        'unit_price' => 'string',
        'tax_rate' => 'string',
        'discount_rate' => 'string',
        'line_total' => 'string',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrderModel::class, 'sales_order_id');
    }
}
