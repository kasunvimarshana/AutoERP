<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use HasFactory, TenantAware, HasUuid;

    // Append-only: No updates or soft deletes allowed
    // Once created, stock ledger entries are immutable

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_id',
        'product_variant_id',
        'batch_id',
        'transaction_type',
        'transaction_date',
        'reference_type',
        'reference_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'running_balance',
        'notes',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'running_balance' => 'decimal:4',
        'metadata' => 'json',
    ];

    // Transaction types
    const TYPE_IN = 'in';           // Stock in (purchase, production, return)
    const TYPE_OUT = 'out';         // Stock out (sale, consumption, damage)
    const TYPE_TRANSFER = 'transfer'; // Transfer between branches
    const TYPE_ADJUSTMENT = 'adjustment'; // Stock adjustment (reconciliation)

    /**
     * Get the tenant that owns the ledger entry
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the branch for this ledger entry
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the product for this ledger entry
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant for this ledger entry
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the batch for this ledger entry
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the user who created this ledger entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Disable updates on stock ledger (append-only)
     */
    public function update(array $attributes = [], array $options = [])
    {
        throw new \Exception('Stock ledger entries cannot be updated. Create a new adjustment entry instead.');
    }

    /**
     * Disable deletes on stock ledger (append-only)
     */
    public function delete()
    {
        throw new \Exception('Stock ledger entries cannot be deleted. Create a new adjustment entry instead.');
    }

    /**
     * Disable force deletes on stock ledger (append-only)
     */
    public function forceDelete()
    {
        throw new \Exception('Stock ledger entries cannot be deleted. Create a new adjustment entry instead.');
    }

    /**
     * Get signed quantity (negative for OUT transactions)
     */
    public function getSignedQuantityAttribute(): float
    {
        return $this->transaction_type === self::TYPE_OUT ? 
               -abs($this->quantity) : 
               abs($this->quantity);
    }

    /**
     * Check if transaction is inbound
     */
    public function isInbound(): bool
    {
        return $this->transaction_type === self::TYPE_IN;
    }

    /**
     * Check if transaction is outbound
     */
    public function isOutbound(): bool
    {
        return $this->transaction_type === self::TYPE_OUT;
    }

    /**
     * Scope to get entries for a specific branch
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get entries for a specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get entries for a specific variant
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    /**
     * Scope to get entries for a specific batch
     */
    public function scopeForBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Scope to get inbound transactions
     */
    public function scopeInbound($query)
    {
        return $query->where('transaction_type', self::TYPE_IN);
    }

    /**
     * Scope to get outbound transactions
     */
    public function scopeOutbound($query)
    {
        return $query->where('transaction_type', self::TYPE_OUT);
    }

    /**
     * Scope to get transactions within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
