<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment record associated with an Order.
 *
 * @property string $id
 * @property string $order_id
 * @property string $tenant_id
 * @property float  $amount
 * @property string $currency
 * @property string $payment_method
 * @property string $gateway_transaction_id
 * @property string $status  pending|captured|refunded|failed
 */
class Payment extends Model
{
    use HasFactory, HasUuids;

    /** @var array<string> */
    protected $fillable = [
        'order_id',
        'tenant_id',
        'amount',
        'currency',
        'payment_method',
        'gateway_transaction_id',
        'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'amount' => 'float',
    ];

    /**
     * @return BelongsTo<Order, Payment>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
