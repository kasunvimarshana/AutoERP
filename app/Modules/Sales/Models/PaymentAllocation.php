<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentAllocation extends BaseModel
{
    protected $table = 'payment_allocations';

    protected $fillable = [
        'payment_id',
        'invoice_type',
        'invoice_id',
        'allocated_amount',
        'allocated_at'
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:4'
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\Payment::class, 'payment_id');
    }
}
