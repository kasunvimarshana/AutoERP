<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Ledger Model (Append-Only)
 *
 * Immutable stock movement tracking.
 * Supports FIFO/FEFO, batch/lot/serial tracking, and expiry handling.
 * All stock movements are recorded as append-only entries for audit trail.
 */
class StockLedger extends Model
{
    // Disable updated_at as this is append-only
    const UPDATED_AT = null;

    protected $table = 'stock_ledger';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'branch_id',
        'warehouse_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'batch_number',
        'lot_number',
        'serial_number',
        'manufacture_date',
        'expiry_date',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
    ];

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenant\Models\Branch::class);
    }

    /**
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic).
     */
    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Check if this is an incoming transaction.
     */
    public function isIncoming(): bool
    {
        return in_array($this->transaction_type, [
            'purchase', 'transfer_in', 'adjustment_in', 'return', 'production',
        ]);
    }

    /**
     * Check if this is an outgoing transaction.
     */
    public function isOutgoing(): bool
    {
        return in_array($this->transaction_type, [
            'sale', 'transfer_out', 'adjustment_out',
        ]);
    }

    /**
     * Check if item has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if item is near expiry (within 30 days).
     */
    public function isNearExpiry(int $days = 30): bool
    {
        return $this->expiry_date &&
               $this->expiry_date->isFuture() &&
               $this->expiry_date->diffInDays(now()) <= $days;
    }

    /**
     * Scope for specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for specific branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for incoming transactions.
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('transaction_type', [
            'purchase', 'transfer_in', 'adjustment_in', 'return', 'production',
        ]);
    }

    /**
     * Scope for outgoing transactions.
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('transaction_type', [
            'sale', 'transfer_out', 'adjustment_out',
        ]);
    }
}
