<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends TenantAwareModel
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id', 'user_id', 'status', 'total_amount',
        'shipping_address', 'notes', 'saga_state', 'cancelled_at', 'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'saga_state' => 'array',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sagaLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SagaLog::class);
    }

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
    public function canBeCancelled(): bool { return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]); }
}
