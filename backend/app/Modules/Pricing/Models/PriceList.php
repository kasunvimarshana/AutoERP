<?php

namespace App\Modules\Pricing\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantAware;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceList extends Model
{
    use HasFactory, SoftDeletes, TenantAware, HasUuid;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'currency',
        'is_default',
        'valid_from',
        'valid_until',
        'priority',
        'rules',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'priority' => 'integer',
        'rules' => 'json',
        'is_active' => 'boolean',
        'metadata' => 'json',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the price list
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the price list items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    /**
     * Check if price list is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if price list is expired
     */
    public function isExpired(): bool
    {
        if (!$this->valid_until) {
            return false;
        }

        return now()->gt($this->valid_until);
    }

    /**
     * Get price for a product
     */
    public function getPriceForProduct(int $productId, int $variantId = null, float $quantity = 1)
    {
        $query = $this->items()
            ->where('product_id', $productId)
            ->where('is_active', true);

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }

        // Get items that match quantity range
        $query->where(function ($q) use ($quantity) {
            $q->where(function ($subQ) use ($quantity) {
                $subQ->whereNotNull('min_quantity')
                    ->where('min_quantity', '<=', $quantity);
            })->where(function ($subQ) use ($quantity) {
                $subQ->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            });
        });

        // Get valid items
        $query->where(function ($q) {
            $now = now();
            $q->where(function ($subQ) use ($now) {
                $subQ->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })->where(function ($subQ) use ($now) {
                $subQ->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            });
        });

        return $query->orderBy('priority', 'desc')->first();
    }

    /**
     * Scope to get active price lists
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get valid price lists
     */
    public function scopeValid($query)
    {
        $now = now();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $now);
            });
    }

    /**
     * Scope to get default price list
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
