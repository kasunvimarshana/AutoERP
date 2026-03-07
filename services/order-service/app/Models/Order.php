<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    // ── Status constants ──────────────────────────────────────────────────────

    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED    = 'shipped';
    public const STATUS_DELIVERED  = 'delivered';
    public const STATUS_CANCELLED  = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    // ── Saga status constants ─────────────────────────────────────────────────

    public const SAGA_STARTED              = 'started';
    public const SAGA_INVENTORY_RESERVED   = 'inventory_reserved';
    public const SAGA_PAYMENT_PROCESSED    = 'payment_processed';
    public const SAGA_COMPLETED            = 'completed';
    public const SAGA_COMPENSATING         = 'compensating';
    public const SAGA_COMPENSATED          = 'compensated';
    public const SAGA_FAILED               = 'failed';

    public const SAGA_STATUSES = [
        self::SAGA_STARTED,
        self::SAGA_INVENTORY_RESERVED,
        self::SAGA_PAYMENT_PROCESSED,
        self::SAGA_COMPLETED,
        self::SAGA_COMPENSATING,
        self::SAGA_COMPENSATED,
        self::SAGA_FAILED,
    ];

    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'status',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'shipping_address',
        'billing_address',
        'notes',
        'saga_status',
        'saga_compensation_data',
        'placed_at',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount'           => 'decimal:2',
        'tax_amount'             => 'decimal:2',
        'discount_amount'        => 'decimal:2',
        'shipping_address'       => 'array',
        'billing_address'        => 'array',
        'saga_compensation_data' => 'array',
        'placed_at'              => 'datetime',
        'confirmed_at'           => 'datetime',
        'shipped_at'             => 'datetime',
        'delivered_at'           => 'datetime',
        'cancelled_at'           => 'datetime',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
        'deleted_at'             => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order): void {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }

            if (empty($order->placed_at)) {
                $order->placed_at = now();
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(Str::random(4)) . '-' . now()->format('YmdHis');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer(Builder $query, string $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('order_number', 'like', "%{$term}%")
              ->orWhere('customer_name', 'like', "%{$term}%")
              ->orWhere('customer_email', 'like', "%{$term}%");
        });
    }

    public function scopePlacedBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('placed_at', [$from, $to]);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getIsCancellableAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function getIsConfirmableAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->saga_status === self::SAGA_INVENTORY_RESERVED;
    }

    public function getIsShippableAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PROCESSING], true);
    }

    public function getIsDeliverableAttribute(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }
}
