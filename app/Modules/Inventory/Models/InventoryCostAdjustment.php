<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Enums\CostAdjustmentStatus;

final class InventoryCostAdjustment extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'inventory_cost_adjustments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'adjustment_date' => 'date',
            'status' => CostAdjustmentStatus::class,
            'posted_at' => 'datetime',
        ]);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryCostAdjustmentLine::class, 'inventory_cost_adjustment_id');
    }
}
