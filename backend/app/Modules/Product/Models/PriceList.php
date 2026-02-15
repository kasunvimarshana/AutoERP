<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Models\User;

class PriceList extends Model
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
        'name',
        'code',
        'description',
        'type',
        'priority',
        'valid_from',
        'valid_to',
        'discount_type',
        'discount_value',
        'conditions',
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
        'priority' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'discount_value' => 'decimal:2',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the price list items.
     */
    public function items()
    {
        return $this->hasMany(PriceListItem::class);
    }

    /**
     * Get the user who created the price list.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the price list.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if price list is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if price list is valid for given date.
     *
     * @param \DateTime|null $date
     * @return bool
     */
    public function isValidOn($date = null): bool
    {
        $date = $date ?? now();
        
        if ($this->valid_from && $date < $this->valid_from) {
            return false;
        }
        
        if ($this->valid_to && $date > $this->valid_to) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if conditions are met.
     *
     * @param array $context
     * @return bool
     */
    public function checkConditions(array $context = []): bool
    {
        if (empty($this->conditions)) {
            return true;
        }
        
        // Implement condition checking logic here
        // Example conditions: customer_id, location_id, min_quantity, etc.
        
        foreach ($this->conditions as $key => $value) {
            if (!isset($context[$key]) || $context[$key] != $value) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get price for a product.
     *
     * @param int $productId
     * @param float $quantity
     * @return PriceListItem|null
     */
    public function getPriceForProduct(int $productId, float $quantity = 1)
    {
        return $this->items()
            ->where('product_id', $productId)
            ->where(function ($query) use ($quantity) {
                $query->where('min_quantity', '<=', $quantity)
                      ->orWhereNull('min_quantity');
            })
            ->where(function ($query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                      ->orWhereNull('max_quantity');
            })
            ->orderBy('min_quantity', 'desc')
            ->first();
    }

    /**
     * Calculate discount for given amount.
     *
     * @param float $amount
     * @return float
     */
    public function calculateDiscount(float $amount): float
    {
        if (!$this->discount_type || !$this->discount_value) {
            return 0;
        }
        
        if ($this->discount_type === 'flat') {
            return $this->discount_value;
        } elseif ($this->discount_type === 'percentage') {
            return round($amount * ($this->discount_value / 100), 2);
        }
        
        return 0;
    }

    /**
     * Apply discount to amount.
     *
     * @param float $amount
     * @return float
     */
    public function applyDiscount(float $amount): float
    {
        return max(0, $amount - $this->calculateDiscount($amount));
    }
}
