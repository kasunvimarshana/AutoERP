<?php

declare(strict_types=1);

namespace Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Purchasing\Enums\PurchaseOrderStatus;

/**
 * Purchase Order Model
 */
class PurchaseOrder extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id',
        'order_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'delivery_address',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'order_date' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'order_id');
    }

    /**
     * Get the supplier for this purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
