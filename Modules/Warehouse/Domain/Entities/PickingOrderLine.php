<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PickingOrderLine entity.
 *
 * Represents a single product line within a picking order.
 * quantity_requested and quantity_picked are cast to string to enforce BCMath arithmetic.
 */
class PickingOrderLine extends Model
{
    use HasTenant;

    protected $table = 'picking_order_lines';

    protected $fillable = [
        'tenant_id',
        'picking_order_id',
        'product_id',
        'from_bin_id',
        'quantity_requested',
        'quantity_picked',
        'status',
    ];

    protected $casts = [
        'quantity_requested' => 'string',
        'quantity_picked'    => 'string',
    ];

    public function pickingOrder(): BelongsTo
    {
        return $this->belongsTo(PickingOrder::class);
    }
}
