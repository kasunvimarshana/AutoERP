<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * StockReservation entity.
 *
 * Represents a quantity of stock reserved for a specific reference (e.g. a sales order).
 * quantity_reserved is cast to string to enforce BCMath arithmetic.
 */
class StockReservation extends Model
{
    use HasTenant;

    protected $table = 'stock_reservations';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity_reserved',
        'reference_type',
        'reference_id',
        'expires_at',
        'is_fulfilled',
    ];

    protected $casts = [
        'quantity_reserved' => 'string',
        'expires_at'        => 'datetime',
        'is_fulfilled'      => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
