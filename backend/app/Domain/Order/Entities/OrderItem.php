<?php

declare(strict_types=1);

namespace App\Domain\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

/**
 * Domain entity for an Order Line Item.
 */
class OrderItem extends Model
{
    protected $table = 'order_items';

    public $timestamps = true;

    protected $fillable = [
        'order_id',
        'product_id',
        'sku',
        'name',
        'quantity',
        'unit_price',
        'total_price',
        'tax_rate',
        'discount',
        'metadata',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'unit_price'  => 'decimal:4',
        'total_price' => 'decimal:4',
        'tax_rate'    => 'decimal:4',
        'discount'    => 'decimal:4',
        'metadata'    => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Compute line total from quantity and unit price, considering discount. */
    public function computeTotal(): float
    {
        return round(
            ($this->quantity * (float) $this->unit_price) - (float) ($this->discount ?? 0),
            4
        );
    }
}
