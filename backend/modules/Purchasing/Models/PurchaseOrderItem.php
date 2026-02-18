<?php

declare(strict_types=1);

namespace Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

/**
 * Purchase Order Item Model
 */
class PurchaseOrderItem extends BaseModel
{
    use HasFactory;

    protected $table = 'purchase_order_items';

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

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product::class, 'product_id');
    }
}
