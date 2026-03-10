<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'id',
        'tenant_id',
        'order_id',
        'customer_id',
        'amount',
        'currency',
        'status',
        'saga_id',
        'provider_reference',
        'refund_id',
        'metadata',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'metadata' => 'array',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED    = 'failed';
    const STATUS_REFUNDED  = 'refunded';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Payment $payment) {
            if (empty($payment->id)) {
                $payment->id = (string) Str::uuid();
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
