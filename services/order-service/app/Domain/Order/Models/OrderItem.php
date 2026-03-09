<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Item Model - line items within an order.
 *
 * @property string $id
 * @property string $order_id
 * @property string $product_id
 * @property string $warehouse_id
 * @property string $product_name
 * @property string $product_sku
 * @property int    $quantity
 * @property float  $unit_price
 * @property float  $total_price
 */
class OrderItem extends Model
{
    use HasUuids;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'warehouse_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
