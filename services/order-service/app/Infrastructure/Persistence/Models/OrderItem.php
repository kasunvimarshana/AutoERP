<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderItem Eloquent Model
 */
class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_code',
        'product_sku',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_amount',
        'line_total',
        'currency',
        'metadata',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'unit_price'      => 'float',
        'discount_amount' => 'float',
        'tax_amount'      => 'float',
        'line_total'      => 'float',
        'metadata'        => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
