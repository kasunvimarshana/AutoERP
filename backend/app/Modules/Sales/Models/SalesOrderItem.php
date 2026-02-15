<?php

declare(strict_types=1);

namespace App\Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductVariant;
use App\Modules\MasterData\Models\UnitOfMeasure;
use App\Modules\MasterData\Models\TaxRate;

class SalesOrderItem extends Model
{
    use HasFactory, SoftDeletes, HasUuid, TenantScoped;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'sales_order_id',
        'product_id',
        'variant_id',
        'item_name',
        'item_description',
        'quantity',
        'unit_id',
        'unit_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_rate_id',
        'tax_percentage',
        'tax_amount',
        'subtotal',
        'total',
        'quantity_fulfilled',
        'quantity_invoiced',
        'notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity_fulfilled' => 'decimal:4',
        'quantity_invoiced' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the sales order.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the unit of measure.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    /**
     * Get the tax rate.
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Calculate remaining quantity to fulfill.
     */
    public function getRemainingQuantityAttribute(): float
    {
        return (float)$this->quantity - (float)$this->quantity_fulfilled;
    }

    /**
     * Check if item is fully fulfilled.
     */
    public function isFullyFulfilled(): bool
    {
        return $this->quantity_fulfilled >= $this->quantity;
    }

    /**
     * Check if item is partially fulfilled.
     */
    public function isPartiallyFulfilled(): bool
    {
        return $this->quantity_fulfilled > 0 && $this->quantity_fulfilled < $this->quantity;
    }
}
