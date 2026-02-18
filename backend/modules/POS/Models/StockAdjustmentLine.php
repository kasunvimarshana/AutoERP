<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Stock Adjustment Line Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $adjustment_id
 * @property string $product_id
 * @property string|null $variation_id
 * @property float $quantity
 * @property float $unit_cost
 * @property float $line_total
 * @property string|null $lot_number
 */
class StockAdjustmentLine extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_stock_adjustment_lines';

    protected $fillable = [
        'tenant_id',
        'adjustment_id',
        'product_id',
        'variation_id',
        'quantity',
        'unit_cost',
        'line_total',
        'lot_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
}
