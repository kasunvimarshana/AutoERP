<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Inventory\Models\Product;

/**
 * Quotation Item Model for AutoERP
 *
 * Represents individual line items within a quotation
 * with pricing, discounts, and tax calculations.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $quotation_id
 * @property string $product_id
 * @property string $product_name
 * @property string|null $product_sku
 * @property string|null $description
 * @property float $quantity
 * @property string $uom
 * @property float $unit_price
 * @property float $discount_percentage
 * @property float $discount_amount
 * @property float $tax_percentage
 * @property float $tax_amount
 * @property float $line_total
 * @property int $line_number
 * @property array|null $custom_attributes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class QuotationItem extends BaseModel
{
    use HasFactory;

    protected $table = 'quotation_items';

    protected $fillable = [
        'tenant_id',
        'quotation_id',
        'product_id',
        'product_name',
        'product_sku',
        'description',
        'quantity',
        'uom',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'line_total',
        'line_number',
        'custom_attributes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'line_number' => 'integer',
        'custom_attributes' => 'array',
    ];

    protected $attributes = [
        'uom' => 'Unit',
        'discount_percentage' => 0,
        'discount_amount' => 0,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'line_number' => 0,
    ];

    /**
     * Get the quotation this item belongs to.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    /**
     * Get the product associated with this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Calculate and update line total with tax and discount.
     */
    public function calculateLineTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        $discountAmount = $this->discount_percentage > 0
            ? ($subtotal * $this->discount_percentage / 100)
            : $this->discount_amount;

        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * $this->tax_percentage / 100;

        $lineTotal = $afterDiscount + $taxAmount;

        $this->discount_amount = $discountAmount;
        $this->tax_amount = $taxAmount;
        $this->line_total = $lineTotal;

        return $lineTotal;
    }

    /**
     * Boot method to auto-calculate totals.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateLineTotal();
        });
    }
}
