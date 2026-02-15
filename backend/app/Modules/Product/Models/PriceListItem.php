<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class PriceListItem extends Model
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
        'price_list_id',
        'product_id',
        'price',
        'min_quantity',
        'max_quantity',
        'discount_type',
        'discount_value',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the price list that owns this item.
     */
    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * Get the product for this price list item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created the item.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the item.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if item is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->priceList && $this->priceList->isActive();
    }

    /**
     * Check if quantity is within range.
     *
     * @param float $quantity
     * @return bool
     */
    public function isQuantityInRange(float $quantity): bool
    {
        $minValid = $this->min_quantity === null || $quantity >= $this->min_quantity;
        $maxValid = $this->max_quantity === null || $quantity <= $this->max_quantity;
        
        return $minValid && $maxValid;
    }

    /**
     * Calculate discount amount.
     *
     * @return float
     */
    public function calculateDiscount(): float
    {
        if (!$this->discount_type || !$this->discount_value) {
            return 0;
        }
        
        if ($this->discount_type === 'flat') {
            return $this->discount_value;
        } elseif ($this->discount_type === 'percentage') {
            return round($this->price * ($this->discount_value / 100), 2);
        }
        
        return 0;
    }

    /**
     * Get final price after discount.
     *
     * @return float
     */
    public function getFinalPrice(): float
    {
        return max(0, $this->price - $this->calculateDiscount());
    }

    /**
     * Calculate total for given quantity.
     *
     * @param float $quantity
     * @return float
     */
    public function calculateTotal(float $quantity): float
    {
        return $this->getFinalPrice() * $quantity;
    }
}
