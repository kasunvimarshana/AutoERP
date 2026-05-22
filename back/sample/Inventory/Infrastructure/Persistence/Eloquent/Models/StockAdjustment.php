<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\Enums\StockAdjustmentStatus;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class StockAdjustment extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'stock_adjustments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'status' => StockAdjustmentStatus::class,
            'counted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany('Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\StockAdjustmentLine', 'stock_adjustment_id');
    }
}
