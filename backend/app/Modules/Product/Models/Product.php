<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Modules\MasterData\Models\UnitOfMeasure;
use App\Modules\MasterData\Models\TaxRate;
use App\Models\User;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'slug',
        'description',
        'type',
        'is_active',
        'is_purchasable',
        'is_sellable',
        'buying_price',
        'selling_price',
        'mrp',
        'wholesale_price',
        'buying_unit_id',
        'selling_unit_id',
        'stock_unit_id',
        'unit_conversion_factor',
        'buying_discount_type',
        'buying_discount_value',
        'selling_discount_type',
        'selling_discount_value',
        'profit_margin_type',
        'profit_margin_value',
        'tax_rate_id',
        'is_tax_inclusive',
        'track_inventory',
        'track_serial',
        'track_batch',
        'has_expiry',
        'expiry_alert_days',
        'valuation_method',
        'min_stock_level',
        'max_stock_level',
        'reorder_level',
        'reorder_quantity',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'barcode',
        'manufacturer',
        'brand',
        'model_number',
        'warranty_period',
        'tags',
        'images',
        'attributes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_purchasable' => 'boolean',
        'is_sellable' => 'boolean',
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'unit_conversion_factor' => 'decimal:4',
        'buying_discount_value' => 'decimal:2',
        'selling_discount_value' => 'decimal:2',
        'profit_margin_value' => 'decimal:2',
        'is_tax_inclusive' => 'boolean',
        'track_inventory' => 'boolean',
        'track_serial' => 'boolean',
        'track_batch' => 'boolean',
        'has_expiry' => 'boolean',
        'min_stock_level' => 'decimal:4',
        'max_stock_level' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
        'weight' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
        'images' => 'array',
        'attributes' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the category that the product belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the buying unit of measure.
     */
    public function buyingUnit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'buying_unit_id');
    }

    /**
     * Get the selling unit of measure.
     */
    public function sellingUnit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'selling_unit_id');
    }

    /**
     * Get the stock unit of measure.
     */
    public function stockUnit()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'stock_unit_id');
    }

    /**
     * Get the tax rate for the product.
     */
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Get the variants for the product.
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the user who created the product.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the product.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get pricing rules for the product.
     */
    public function pricingRules()
    {
        return $this->hasMany(\App\Models\PricingRule::class);
    }

    /**
     * Get discount tiers for the product.
     */
    public function discountTiers()
    {
        return $this->hasMany(\App\Models\DiscountTier::class);
    }

    /**
     * Get selling discount tiers.
     */
    public function sellingDiscountTiers()
    {
        return $this->hasMany(\App\Models\DiscountTier::class)
            ->where('tier_type', 'selling')
            ->orderBy('min_quantity');
    }

    /**
     * Get buying discount tiers.
     */
    public function buyingDiscountTiers()
    {
        return $this->hasMany(\App\Models\DiscountTier::class)
            ->where('tier_type', 'buying')
            ->orderBy('min_quantity');
    }

    /**
     * Check if product is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Calculate the final selling price after discount.
     *
     * @return float
     */
    public function getFinalSellingPrice(): float
    {
        $price = $this->selling_price;
        
        if ($this->selling_discount_type === 'flat') {
            $price -= $this->selling_discount_value;
        } elseif ($this->selling_discount_type === 'percentage') {
            $price -= ($price * $this->selling_discount_value / 100);
        }
        
        return max(0, $price);
    }

    /**
     * Calculate the final buying price after discount.
     *
     * @return float
     */
    public function getFinalBuyingPrice(): float
    {
        $price = $this->buying_price;
        
        if ($this->buying_discount_type === 'flat') {
            $price -= $this->buying_discount_value;
        } elseif ($this->buying_discount_type === 'percentage') {
            $price -= ($price * $this->buying_discount_value / 100);
        }
        
        return max(0, $price);
    }

    /**
     * Calculate profit margin.
     *
     * @return float
     */
    public function calculateProfitMargin(): float
    {
        $buyingPrice = $this->getFinalBuyingPrice();
        $sellingPrice = $this->getFinalSellingPrice();
        
        if ($buyingPrice == 0) {
            return 0;
        }
        
        return (($sellingPrice - $buyingPrice) / $buyingPrice) * 100;
    }
}
