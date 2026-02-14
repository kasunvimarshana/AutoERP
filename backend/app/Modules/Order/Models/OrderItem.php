<?php

namespace App\Modules\Order\Models;

use App\Base\BaseModel;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="OrderItem",
 *     type="object",
 *     title="OrderItem",
 *     description="Order item model (line items)",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="order_id", type="string", format="uuid"),
 *     @OA\Property(property="product_variant_id", type="string", format="uuid"),
 *     @OA\Property(property="quantity", type="number", format="float", example=2),
 *     @OA\Property(property="unit_price", type="number", format="float", example=100.00),
 *     @OA\Property(property="tax_rate", type="number", format="float", example=0.15),
 *     @OA\Property(property="discount_amount", type="number", format="float", example=10.00),
 *     @OA\Property(property="line_total", type="number", format="float", example=190.00),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class OrderItem extends BaseModel
{
    protected $fillable = [
        'order_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_amount',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function calculateLineTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        $afterDiscount = $subtotal - $this->discount_amount;
        $taxAmount = $afterDiscount * $this->tax_rate;
        return $afterDiscount + $taxAmount;
    }

    public function getTaxAmount(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        $afterDiscount = $subtotal - $this->discount_amount;
        return $afterDiscount * $this->tax_rate;
    }

    public function getSubtotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function scopeByOrder($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByProduct($query, string $productVariantId)
    {
        return $query->where('product_variant_id', $productVariantId);
    }
}
