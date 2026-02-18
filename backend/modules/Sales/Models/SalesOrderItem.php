<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

/**
 * Sales Order Item Model
 *
 * @property string $id
 * @property string $order_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property float $quantity
 * @property float $unit_price
 * @property float $tax_rate
 * @property float $discount_amount
 * @property float $total_amount
 */
class SalesOrderItem extends BaseModel
{
    use HasFactory;

    protected $table = 'sales_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product::class, 'product_id');
    }
}
