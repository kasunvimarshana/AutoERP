<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class InventoryCostAdjustmentLine extends TenantOwnedModel
{
    protected $table = 'inventory_cost_adjustment_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'inventory_cost_adjustment_id' => 'integer',
            'valuation_layer_id' => 'integer',
            'adjustment_amount' => 'decimal:6',
            'remaining_quantity' => 'decimal:6',
            'unit_cost_before' => 'decimal:6',
            'unit_cost_after' => 'decimal:6',
            'remaining_value_before' => 'decimal:6',
            'remaining_value_after' => 'decimal:6',
        ]);
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryCostAdjustment::class, 'inventory_cost_adjustment_id');
    }

    public function valuationLayer(): BelongsTo
    {
        return $this->belongsTo(InventoryValuationLayer::class, 'valuation_layer_id');
    }
}
