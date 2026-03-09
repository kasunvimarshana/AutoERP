<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMMITTED = 'committed';
    public const STATUS_RELEASED  = 'released';
    public const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'reference_id',
        'reference_type',
        'status',
        'notes',
        'metadata',
        'expires_at',
        'committed_at',
        'released_at',
        'committed_by',
        'released_by',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'metadata'     => 'array',
        'expires_at'   => 'datetime',
        'committed_at' => 'datetime',
        'released_at'  => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where('expires_at', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function canBeCommitted(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    public function canBeReleased(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
