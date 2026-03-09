<?php

declare(strict_types=1);

namespace App\Domain\Order\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order Model
 *
 * Represents an order in the distributed SaaS inventory system.
 * Participates in Saga distributed transactions.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_id
 * @property string $saga_id        Links to the Saga orchestrating this order
 * @property string $status         pending, confirmed, processing, shipped, delivered, cancelled
 * @property float  $total_amount
 * @property string $currency
 * @property array|null $shipping_address
 * @property string|null $notes
 */
class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'orders';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'saga_id',
        'status',
        'total_amount',
        'currency',
        'shipping_address',
        'notes',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'shipping_address' => 'array',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the line items for this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Check if the order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ], true);
    }
}
