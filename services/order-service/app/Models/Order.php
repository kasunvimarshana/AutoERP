<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'tenant_id',
        'customer_id',
        'status',
        'total_amount',
        'currency',
        'items',
        'saga_id',
        'saga_status',
        'payment_id',
        'metadata',
    ];

    protected $casts = [
        'items'        => 'array',
        'metadata'     => 'array',
        'total_amount' => 'decimal:2',
    ];

    // Order status constants
    const STATUS_PENDING              = 'pending';
    const STATUS_INVENTORY_RESERVING  = 'inventory_reserving';  // Inventory reservation in progress
    const STATUS_PAYMENT_PROCESSING   = 'payment_processing';   // Inventory reserved, payment pending
    const STATUS_PAYMENT_FAILED       = 'payment_failed';
    const STATUS_INVENTORY_RESERVED  = 'inventory_reserved';
    const STATUS_CONFIRMED           = 'confirmed';
    const STATUS_CANCELLED           = 'cancelled';
    const STATUS_COMPENSATING        = 'compensating';
    const STATUS_COMPENSATED         = 'compensated';

    // Saga status constants
    const SAGA_STARTED     = 'started';
    const SAGA_COMPLETED   = 'completed';
    const SAGA_FAILED      = 'failed';
    const SAGA_COMPENSATED = 'compensated';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Order $order) {
            if (empty($order->id)) {
                $order->id = (string) Str::uuid();
            }
            if (empty($order->saga_id)) {
                $order->saga_id = 'saga-' . (string) Str::uuid();
            }
        });
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getIncrementing(): bool
    {
        return false;
    }
}
