<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

/**
 * Invoice Item Model
 *
 * Represents a line item in an invoice.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $invoice_id
 * @property string|null $product_id
 * @property string $description
 * @property float $quantity
 * @property float $unit_price
 * @property float $tax_rate
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class InvoiceItem extends BaseModel
{
    use HasFactory;

    protected $table = 'invoice_items';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the invoice that owns this item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Get the product associated with this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product::class, 'product_id');
    }
}
