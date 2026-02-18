<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Transaction Line Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $transaction_id
 * @property string $product_id
 * @property string|null $variation_id
 * @property float $quantity
 * @property string|null $unit
 * @property float $unit_price
 * @property float $discount_amount
 * @property string|null $discount_type
 * @property string|null $tax_rate_id
 * @property float $tax_amount
 * @property float $line_total
 * @property string|null $lot_number
 * @property \Carbon\Carbon|null $expiry_date
 * @property string|null $notes
 */
class TransactionLine extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_transaction_lines';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'product_id',
        'variation_id',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'discount_type',
        'tax_rate_id',
        'tax_amount',
        'line_total',
        'lot_number',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }
}
