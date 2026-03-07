<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';

    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'customer_email',
        'items',
        'total_amount',
        'status',
        'saga_id',
        'saga_state',
    ];

    protected $casts = [
        'items'        => 'array',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order): void {
            if (empty($order->id)) {
                $order->id = Uuid::uuid4()->toString();
            }
        });
    }

    public function sagaStates(): HasMany
    {
        return $this->hasMany(SagaState::class, 'order_id', 'id');
    }

    public function isCompletable(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }
}
