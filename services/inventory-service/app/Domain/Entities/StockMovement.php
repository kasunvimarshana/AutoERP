<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Movement Entity
 *
 * Records every stock in/out movement for audit trail and inventory tracking.
 */
class StockMovement extends Model
{
    protected $table = 'stock_movements';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'type',         // in | out | adjustment | transfer
        'quantity',
        'reference',    // order_id, purchase_id, etc.
        'reference_type',
        'notes',
        'performed_by', // user_id
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
