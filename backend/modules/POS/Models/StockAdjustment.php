<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Stock Adjustment Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property string $reference_number
 * @property \Carbon\Carbon $adjustment_date
 * @property string $type
 * @property float $total_amount
 * @property string|null $reason
 * @property string $created_by
 */
class StockAdjustment extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_stock_adjustments';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'reference_number',
        'adjustment_date',
        'type',
        'total_amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'adjustment_date' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockAdjustmentLine::class, 'adjustment_id');
    }
}
