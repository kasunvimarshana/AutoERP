<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * SalesOrderLine entity.
 *
 * All quantity and monetary properties are cast to string for BCMath precision.
 */
class SalesOrderLine extends Model
{
    use HasTenant;

    protected $table = 'sales_order_lines';

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity'        => 'string',
        'unit_price'      => 'string',
        'discount_amount' => 'string',
        'tax_rate'        => 'string',
        'line_total'      => 'string',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }
}
