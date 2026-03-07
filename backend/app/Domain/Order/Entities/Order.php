<?php

declare(strict_types=1);

namespace App\Domain\Order\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant;
use App\Models\OrderItem;
use App\Models\User;

/**
 * Domain entity for an Order in the Order bounded context.
 */
class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'orders';

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
        'total',
        'currency',
        'notes',
        'shipping_address',
        'billing_address',
        'metadata',
        'saga_id',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal'         => 'decimal:4',
        'tax'              => 'decimal:4',
        'discount'         => 'decimal:4',
        'total'            => 'decimal:4',
        'shipping_address' => 'array',
        'billing_address'  => 'array',
        'metadata'         => 'array',
        'completed_at'     => 'datetime',
        'cancelled_at'     => 'datetime',
    ];

    /** Valid order status transitions map. */
    public const STATUS_TRANSITIONS = [
        'pending'    => ['confirmed', 'cancelled'],
        'confirmed'  => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered', 'returned'],
        'delivered'  => ['returned', 'completed'],
        'completed'  => [],
        'cancelled'  => [],
        'returned'   => ['completed'],
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // -------------------------------------------------------------------------
    // Domain behaviour
    // -------------------------------------------------------------------------

    /**
     * Determine whether a status transition is allowed.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Transition the order to a new status.
     *
     * @throws \DomainException When the transition is not allowed.
     */
    public function transitionTo(string $newStatus, array $metadata = []): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Order cannot transition from '{$this->status}' to '{$newStatus}'."
            );
        }

        $this->status = $newStatus;

        if ($newStatus === 'completed') {
            $this->completed_at = now();
        } elseif ($newStatus === 'cancelled') {
            $this->cancelled_at = now();
        }

        if (!empty($metadata)) {
            $this->metadata = array_merge($this->metadata ?? [], $metadata);
        }

        $this->save();
    }

    /** Calculate and set totals from line items. */
    public function recalculateTotals(): void
    {
        $this->loadMissing('items');

        $subtotal = $this->items->sum(
            fn (OrderItem $item) => $item->quantity * $item->unit_price
        );

        $this->subtotal = $subtotal;
        $this->total    = $subtotal + ($this->tax ?? 0) - ($this->discount ?? 0);
        $this->save();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, int|string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus(\Illuminate\Database\Eloquent\Builder $query, string $status): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', $status);
    }
}
