<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SagaLog extends Model
{
    const UPDATABLE_TIMESTAMPS = false;

    protected $fillable = [
        'order_id', 'step_name', 'status', 'payload',
        'error_message', 'executed_at', 'compensated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'executed_at' => 'datetime',
        'compensated_at' => 'datetime',
    ];

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
