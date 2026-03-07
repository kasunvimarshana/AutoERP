<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SagaState extends Model
{
    public const STATUS_STARTED                = 'STARTED';
    public const STATUS_INVENTORY_RESERVED     = 'INVENTORY_RESERVED';
    public const STATUS_PAYMENT_PROCESSED      = 'PAYMENT_PROCESSED';
    public const STATUS_COMPLETED              = 'COMPLETED';
    public const STATUS_COMPENSATION_STARTED   = 'COMPENSATION_STARTED';
    public const STATUS_COMPENSATION_COMPLETED = 'COMPENSATION_COMPLETED';
    public const STATUS_FAILED                 = 'FAILED';

    protected $fillable = [
        'saga_id',
        'order_id',
        'current_step',
        'status',
        'compensation_data',
        'error_message',
    ];

    protected $casts = [
        'compensation_data' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
