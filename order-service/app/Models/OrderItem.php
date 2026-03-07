<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order line item.
 *
 * @property string $id
 * @property string $order_id
 * @property string $product_id
 * @property int    $quantity
 * @property float  $unit_price
 * @property float  $subtotal
 */
class OrderItem extends Model
{
    use HasFactory, HasUuids;

    /** @var array<string> */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'float',
        'subtotal'   => 'float',
    ];

    /**
     * @return BelongsTo<Order, OrderItem>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
