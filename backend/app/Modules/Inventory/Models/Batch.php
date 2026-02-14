<?php

namespace App\Modules\Inventory\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Organization\Models\Branch;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_variant_id',
        'branch_id',
        'batch_number',
        'lot_number',
        'serial_number',
        'quantity',
        'cost_price',
        'selling_price',
        'manufactured_date',
        'expiry_date',
        'supplier_id',
        'supplier_reference',
        'notes',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'manufactured_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'json',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the batch
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the product that owns the batch
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant that owns the batch
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the branch where the batch is located
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the stock ledger entries for the batch
     */
    public function stockLedgers(): HasMany
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Check if batch is expiring soon (within specified days)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->diffInDays(now()) <= $days && !$this->isExpired();
    }

    /**
     * Get remaining quantity
     */
    public function getRemainingQuantity(): float
    {
        return (float) $this->stockLedgers()->sum('quantity');
    }

    /**
     * Check if batch is available
     */
    public function isAvailable(): bool
    {
        return $this->is_active && 
               !$this->isExpired() && 
               $this->getRemainingQuantity() > 0;
    }

    /**
     * Scope to get non-expired batches
     */
    public function scopeNonExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>', now());
        });
    }

    /**
     * Scope to get expiring soon batches
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                     ->whereDate('expiry_date', '>', now())
                     ->whereDate('expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Scope to get expired batches
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                     ->whereDate('expiry_date', '<=', now());
    }
}
