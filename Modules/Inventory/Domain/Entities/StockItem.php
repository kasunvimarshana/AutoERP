<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * StockItem entity.
 *
 * Represents the current stock level of a specific product in a warehouse location.
 * All quantity and cost columns are cast to string to enforce BCMath arithmetic.
 */
class StockItem extends Model
{
    use HasTenant;

    protected $table = 'stock_items';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'stock_location_id',
        'product_id',
        'uom_id',
        'batch_number',
        'lot_number',
        'serial_number',
        'expiry_date',
        'quantity_on_hand',
        'quantity_reserved',
        'quantity_available',
        'costing_method',
        'cost_price',
    ];

    protected $casts = [
        'quantity_on_hand'   => 'string',
        'quantity_reserved'  => 'string',
        'quantity_available' => 'string',
        'cost_price'         => 'string',
        'expiry_date'        => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }
}
