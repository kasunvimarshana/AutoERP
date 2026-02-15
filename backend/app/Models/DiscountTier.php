<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DiscountTier Model
 * 
 * Manages tiered discounts for products and pricing rules
 */
class DiscountTier extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'pricing_rule_id',
        'tier_type',
        'min_quantity',
        'max_quantity',
        'discount_type',
        'discount_value',
        'fixed_price',
        'label',
        'display_order',
    ];

    protected $casts = [
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'discount_value' => 'decimal:2',
        'fixed_price' => 'decimal:2',
        'display_order' => 'integer',
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
     * Pricing rule relationship
     */
    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    /**
     * Check if tier applies to quantity
     */
    public function appliesToQuantity(float $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discounted price
     */
    public function calculateDiscountedPrice(float $basePrice): float
    {
        if ($this->fixed_price) {
            return $this->fixed_price;
        }

        if ($this->discount_type === 'flat') {
            return max(0, $basePrice - $this->discount_value);
        }

        // Percentage discount
        return max(0, $basePrice - ($basePrice * $this->discount_value / 100));
    }

    /**
     * Get discount amount
     */
    public function getDiscountAmount(float $basePrice): float
    {
        $discountedPrice = $this->calculateDiscountedPrice($basePrice);
        return $basePrice - $discountedPrice;
    }

    /**
     * Scope: For product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: For pricing rule
     */
    public function scopeForPricingRule($query, int $pricingRuleId)
    {
        return $query->where('pricing_rule_id', $pricingRuleId);
    }

    /**
     * Scope: By tier type (buying/selling)
     */
    public function scopeByType($query, string $tierType)
    {
        return $query->where('tier_type', $tierType);
    }

    /**
     * Scope: Order by quantity
     */
    public function scopeOrderByQuantity($query, string $direction = 'asc')
    {
        return $query->orderBy('min_quantity', $direction);
    }

    /**
     * Scope: Order by display
     */
    public function scopeOrderByDisplay($query)
    {
        return $query->orderBy('display_order')->orderBy('min_quantity');
    }
}
