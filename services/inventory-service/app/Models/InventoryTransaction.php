<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    /**
     * Transactions are immutable audit records – no updates allowed.
     */
    public const UPDATED_AT = null;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'inventory_id',
        'product_id',
        'type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'reference_type',
        'reference_id',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'inventory_id'      => 'integer',
        'product_id'        => 'integer',
        'quantity'          => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity'      => 'integer',
        'created_at'        => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Transaction types
    // -------------------------------------------------------------------------

    public const TYPE_RECEIPT     = 'receipt';
    public const TYPE_ADJUSTMENT  = 'adjustment';
    public const TYPE_RESERVATION = 'reservation';
    public const TYPE_RELEASE     = 'release';
    public const TYPE_SALE        = 'sale';

    public const VALID_TYPES = [
        self::TYPE_RECEIPT,
        self::TYPE_ADJUSTMENT,
        self::TYPE_RESERVATION,
        self::TYPE_RELEASE,
        self::TYPE_SALE,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByInventory(Builder $query, int $inventoryId): Builder
    {
        return $query->where('inventory_id', $inventoryId);
    }

    public function scopeByProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeAfterDate(Builder $query, string $from): Builder
    {
        return $query->where('created_at', '>=', $from);
    }

    public function scopeBeforeDate(Builder $query, string $to): Builder
    {
        return $query->where('created_at', '<=', $to);
    }
}
