<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * PosTransactionLine entity.
 *
 * Quantity and monetary amounts are cast to string for BCMath precision.
 */
class PosTransactionLine extends Model
{
    use HasTenant;

    protected $table = 'pos_transaction_lines';

    protected $fillable = [
        'tenant_id',
        'pos_transaction_id',
        'product_id',
        'uom_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity'        => 'string',
        'unit_price'      => 'string',
        'discount_amount' => 'string',
        'line_total'      => 'string',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}
