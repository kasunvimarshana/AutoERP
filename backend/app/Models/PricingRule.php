<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * PricingRule Model
 * 
 * Manages dynamic pricing rules for products, categories, customers, and seasonal pricing
 */
class PricingRule extends Model
{
    use HasFactory, HasUuid, TenantScoped, Auditable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'rule_type',
        'is_active',
        'product_id',
        'category_id',
        'customer_id',
        'customer_group',
        'priority',
        'valid_from',
        'valid_to',
        'time_from',
        'time_to',
        'days_of_week',
        'min_quantity',
        'max_quantity',
        'pricing_method',
        'adjustment_type',
        'adjustment_value',
        'fixed_price',
        'can_compound',
        'exclude_rules',
        'conditions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'days_of_week' => 'array',
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'adjustment_value' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'can_compound' => 'boolean',
        'exclude_rules' => 'array',
        'conditions' => 'array',
    ];

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Product relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Product category relationship
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Customer relationship
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Discount tiers relationship
     */
    public function discountTiers(): HasMany
    {
        return $this->hasMany(DiscountTier::class);
    }

    /**
     * Creator relationship
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater relationship
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if rule is currently valid
     */
    public function isCurrentlyValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // Check date range
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_to && $now->gt($this->valid_to)) {
            return false;
        }

        // Check day of week
        if ($this->days_of_week && !in_array($now->dayOfWeek, $this->days_of_week)) {
            return false;
        }

        // Check time range
        if ($this->time_from && $this->time_to) {
            $currentTime = $now->format('H:i:s');
            if ($currentTime < $this->time_from || $currentTime > $this->time_to) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if rule applies to given quantity
     */
    public function appliesToQuantity(float $quantity): bool
    {
        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    /**
     * Calculate adjusted price
     */
    public function calculateAdjustedPrice(float $basePrice): float
    {
        switch ($this->pricing_method) {
            case 'fixed':
                return $this->fixed_price ?? $basePrice;

            case 'markup':
                return $this->applyAdjustment($basePrice, $this->adjustment_value, true);

            case 'markdown':
            case 'discount':
                return $this->applyAdjustment($basePrice, $this->adjustment_value, false);

            default:
                return $basePrice;
        }
    }

    /**
     * Apply adjustment to price
     */
    protected function applyAdjustment(float $price, float $value, bool $isIncrease): float
    {
        if ($this->adjustment_type === 'flat') {
            return $isIncrease 
                ? $price + $value 
                : max(0, $price - $value);
        }

        // Percentage adjustment
        $adjustment = $price * ($value / 100);
        return $isIncrease 
            ? $price + $adjustment 
            : max(0, $price - $adjustment);
    }

    /**
     * Scope: Active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: For product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where(function ($q) use ($productId) {
            $q->where('product_id', $productId)
              ->orWhereNull('product_id');
        });
    }

    /**
     * Scope: For customer
     */
    public function scopeForCustomer($query, ?int $customerId = null, ?string $customerGroup = null)
    {
        return $query->where(function ($q) use ($customerId, $customerGroup) {
            $q->whereNull('customer_id')
              ->whereNull('customer_group');

            if ($customerId) {
                $q->orWhere('customer_id', $customerId);
            }

            if ($customerGroup) {
                $q->orWhere('customer_group', $customerGroup);
            }
        });
    }

    /**
     * Scope: Currently valid
     */
    public function scopeCurrentlyValid($query)
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', $now);
            });
    }

    /**
     * Scope: Order by priority
     */
    public function scopeByPriority($query, string $direction = 'desc')
    {
        return $query->orderBy('priority', $direction);
    }
}
