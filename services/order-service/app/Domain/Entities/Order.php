<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Order Entity
 *
 * Represents an order in the system. Participates in Saga distributed transactions.
 */
class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_number',
        'status',        // pending | confirmed | processing | completed | cancelled | failed
        'saga_id',       // ID of the coordinating Saga transaction
        'total_amount',
        'currency',
        'shipping_address',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
        'metadata' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sagaTransactions(): HasMany
    {
        return $this->hasMany(SagaTransaction::class, 'context->order_id');
    }

    /**
     * Check if order can be cancelled.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }
}
