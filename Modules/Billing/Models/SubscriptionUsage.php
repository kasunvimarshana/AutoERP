<?php

declare(strict_types=1);

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Billing\Enums\UsageType;

class SubscriptionUsage extends Model
{
    use HasFactory;

    protected $table = 'subscription_usages';

    protected $fillable = [
        'subscription_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'recorded_at',
        'metadata',
    ];

    protected $casts = [
        'type' => UsageType::class,
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'recorded_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
