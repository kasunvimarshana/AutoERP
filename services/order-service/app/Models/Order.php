<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    // -------------------------------------------------------------------------
    // Status constants
    // -------------------------------------------------------------------------

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED    = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
    ];

    // -------------------------------------------------------------------------
    // Eloquent configuration
    // -------------------------------------------------------------------------

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'status',
        'total_amount',
        'currency',
        'items',
        'metadata',
        'saga_id',
    ];

    protected $casts = [
        'items'        => 'array',
        'metadata'     => 'array',
        'total_amount' => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function sagaTransactions(): HasMany
    {
        return $this->hasMany(SagaTransaction::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Restrict query to a specific tenant.
     */
    public function scopeByTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Restrict query to orders that are neither cancelled nor failed.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            self::STATUS_CANCELLED,
            self::STATUS_FAILED,
        ]);
    }

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Transition the order to confirmed and persist.
     */
    public function confirm(): bool
    {
        $this->status = self::STATUS_CONFIRMED;

        return $this->save();
    }

    /**
     * Transition the order to cancelled and persist.
     */
    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;

        return $this->save();
    }

    /**
     * Transition the order to failed and persist.
     */
    public function fail(): bool
    {
        $this->status = self::STATUS_FAILED;

        return $this->save();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Safe API representation of the order.
     */
    public function toApiArray(): array
    {
        return [
            'id'           => $this->id,
            'tenant_id'    => $this->tenant_id,
            'customer_id'  => $this->customer_id,
            'status'       => $this->status,
            'total_amount' => $this->total_amount,
            'currency'     => $this->currency,
            'items'        => $this->items,
            'metadata'     => $this->metadata,
            'saga_id'      => $this->saga_id,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
