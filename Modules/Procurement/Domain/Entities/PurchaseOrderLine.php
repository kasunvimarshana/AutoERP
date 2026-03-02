<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PurchaseOrderLine entity.
 *
 * All quantity and monetary values are cast to string for BCMath precision.
 */
class PurchaseOrderLine extends Model
{
    use HasTenant;

    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'product_id'        => 'integer',
        'uom_id'            => 'integer',
        'quantity'          => 'string',
        'unit_cost'         => 'string',
        'line_total'        => 'string',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
