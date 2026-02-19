<?php

declare(strict_types=1);

namespace Modules\Pricing\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Product;

/**
 * PriceListItem Model
 *
 * Represents a product price in a price list
 *
 * @property int $id
 * @property int $price_list_id
 * @property int $product_id
 * @property string $price
 * @property string|null $min_quantity
 * @property string|null $max_quantity
 * @property string|null $discount_percentage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PriceListItem extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    protected $table = 'price_list_items';

    protected $fillable = [
        'price_list_id',
        'product_id',
        'price',
        'min_quantity',
        'max_quantity',
        'discount_percentage',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'max_quantity' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the price list
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for quantity range
     */
    public function scopeForQuantity($query, string $quantity)
    {
        return $query->where(function ($q) use ($quantity) {
            $q->whereNull('min_quantity')
                ->orWhere('min_quantity', '<=', $quantity);
        })->where(function ($q) use ($quantity) {
            $q->whereNull('max_quantity')
                ->orWhere('max_quantity', '>=', $quantity);
        });
    }

    /**
     * Calculate final price with discount
     */
    public function getFinalPrice(): string
    {
        if ($this->discount_percentage) {
            $discount = bcmul($this->price, bcdiv((string) $this->discount_percentage, '100', 4), 2);

            return bcsub($this->price, $discount, 2);
        }

        return $this->price;
    }
}
