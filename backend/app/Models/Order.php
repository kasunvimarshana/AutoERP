<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_number',
        'status',
        'payment_status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'notes',
        'shipping_address',
        'billing_address',
        'metadata',
        'saga_id',
    ];

    protected $casts = [
        'subtotal'         => 'decimal:2',
        'tax'              => 'decimal:2',
        'discount'         => 'decimal:2',
        'total'            => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address'  => 'array',
        'metadata'         => 'array',
    ];

    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED    = 'shipped';
    public const STATUS_DELIVERED  = 'delivered';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_REFUNDED   = 'refunded';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID    = 'paid';
    public const PAYMENT_FAILED  = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    // ─── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ]);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
        $this->subtotal = $subtotal;
        $this->total    = $subtotal + $this->tax - $this->discount;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }
}
