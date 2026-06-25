<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class InventoryValuationConsumption extends TenantOwnedModel
{
    protected $table = 'inventory_valuation_consumptions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'issue_movement_id' => 'integer',
            'valuation_layer_id' => 'integer',
            'quantity_consumed' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:6',
            'reversed_by_movement_id' => 'integer',
            'reversed_at' => 'datetime',
        ]);
    }

    public function issueMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'issue_movement_id');
    }

    public function valuationLayer(): BelongsTo
    {
        return $this->belongsTo(InventoryValuationLayer::class, 'valuation_layer_id');
    }

    public function reversedByMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'reversed_by_movement_id');
    }
}
