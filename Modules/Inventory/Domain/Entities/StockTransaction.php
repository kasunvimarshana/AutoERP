<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * StockTransaction entity.
 *
 * Immutable ledger entry for every stock movement.
 * All quantity and cost columns are cast to string to enforce BCMath arithmetic.
 */
class StockTransaction extends Model
{
    use HasTenant;

    protected $table = 'stock_transactions';

    protected $fillable = [
        'tenant_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'warehouse_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'batch_number',
        'lot_number',
        'serial_number',
        'expiry_date',
        'notes',
        'transacted_at',
        'transacted_by',
        'is_pharmaceutical_compliant',
    ];

    protected $casts = [
        'quantity'                    => 'string',
        'unit_cost'                   => 'string',
        'total_cost'                  => 'string',
        'expiry_date'                 => 'date',
        'transacted_at'               => 'datetime',
        'is_pharmaceutical_compliant' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
