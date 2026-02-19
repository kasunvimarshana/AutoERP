<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * OrderItem Model
 *
 * Line items for sales orders with product, quantity, pricing.
 */
class OrderItem extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'order_id',
        'product_id',
        'description',
        'quantity',
        'unit_id',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'subtotal',
        'total',
        'quantity_shipped',
        'quantity_invoiced',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:6',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'total' => 'decimal:6',
        'quantity_shipped' => 'decimal:6',
        'quantity_invoiced' => 'decimal:6',
        'sort_order' => 'integer',
    ];

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit for this item.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the remaining quantity to ship.
     */
    public function getRemainingToShip(): string
    {
        return bcsub((string) $this->quantity, (string) $this->quantity_shipped, 6);
    }

    /**
     * Get the remaining quantity to invoice.
     */
    public function getRemainingToInvoice(): string
    {
        return bcsub((string) $this->quantity, (string) $this->quantity_invoiced, 6);
    }

    /**
     * Check if item is fully shipped.
     */
    public function isFullyShipped(): bool
    {
        return bccomp((string) $this->quantity_shipped, (string) $this->quantity, 6) >= 0;
    }

    /**
     * Check if item is fully invoiced.
     */
    public function isFullyInvoiced(): bool
    {
        return bccomp((string) $this->quantity_invoiced, (string) $this->quantity, 6) >= 0;
    }
}
