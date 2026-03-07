<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order model.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $user_id
 * @property string      $status   pending|confirmed|cancelled|failed
 * @property float       $total_amount
 * @property string      $currency
 * @property string|null $payment_method
 * @property string|null $notes
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon|null $cancelled_at
 */
class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'total_amount',
        'currency',
        'payment_method',
        'notes',
        'confirmed_at',
        'cancelled_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_amount' => 'float',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Line items for this order.
     *
     * @return HasMany<OrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Payment record for this order.
     *
     * @return HasOne<Payment>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Saga log entries for this order's distributed transaction.
     *
     * @return HasMany<SagaLog>
     */
    public function sagaLogs(): HasMany
    {
        return $this->hasMany(SagaLog::class);
    }
}
