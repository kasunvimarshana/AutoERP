<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'status',
        'subtotal',
        'tax',
        'discount',
        'shipping_cost',
        'total',
        'currency',
        'shipping_address',
        'billing_address',
        'payment_method',
        'payment_status',
        'notes',
        'metadata',
        'saga_transaction_id',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address'  => 'array',
        'metadata'         => 'array',
        'subtotal'         => 'decimal:2',
        'tax'              => 'decimal:2',
        'discount'         => 'decimal:2',
        'shipping_cost'    => 'decimal:2',
        'total'            => 'decimal:2',
        'deleted_at'       => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Status constants
    // -------------------------------------------------------------------------

    const STATUS_PENDING    = 'pending';
    const STATUS_CONFIRMED  = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED    = 'shipped';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_REFUNDED   = 'refunded';

    const PAYMENT_STATUS_PENDING  = 'pending';
    const PAYMENT_STATUS_PAID     = 'paid';
    const PAYMENT_STATUS_FAILED   = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    /**
     * Statuses from which an order may be cancelled.
     */
    const CANCELLABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function sagaTransaction(): HasOne
    {
        return $this->hasOne(SagaTransaction::class, 'id', 'saga_transaction_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isCancellable(): bool
    {
        return in_array($this->status, self::CANCELLABLE_STATUSES, true);
    }

    /**
     * Generate a unique order number: ORD-{TENANTPREFIX}-{YYYYMMDD}-{RANDOM}.
     */
    public static function generateOrderNumber(string $tenantId): string
    {
        $prefix = strtoupper(substr($tenantId, 0, 4));
        $date   = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid('', true)), 0, 6));

        return "ORD-{$prefix}-{$date}-{$random}";
    }
}
