<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Sales\Enums\OrderStatus;

/**
 * Sales Order Model
 *
 * Represents a sales order in the system.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $order_number
 * @property string|null $customer_id
 * @property OrderStatus $status
 * @property \Illuminate\Support\Carbon $order_date
 * @property \Illuminate\Support\Carbon|null $delivery_date
 * @property string|null $billing_address
 * @property string|null $shipping_address
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class SalesOrder extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'status',
        'order_date',
        'delivery_date',
        'billing_address',
        'shipping_address',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'order_date' => 'datetime',
        'delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id');
    }

    /**
     * Get the customer associated with this sales order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
