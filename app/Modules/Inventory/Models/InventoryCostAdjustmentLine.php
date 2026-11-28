<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Enums\CostAdjustmentStatus;

final class InventoryCostAdjustmentLine extends TenantOwnedModel
{
    protected $table = 'inventory_cost_adjustment_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryCostAdjustmentLine $line): void {
            if ($line->isDirty(['tenant_id', 'inventory_cost_adjustment_id'])) {
                throw new LogicException('Structural keys on inventory cost adjustment lines are immutable.');
            }

            $parent = $line->adjustment ?? InventoryCostAdjustment::query()->find($line->inventory_cost_adjustment_id);
            if ($parent !== null) {
                $status = $parent->status instanceof CostAdjustmentStatus ? $parent->status : CostAdjustmentStatus::from((string) $parent->status);
                if ($status === CostAdjustmentStatus::Posted) {
                    throw new LogicException('Lines of posted inventory cost adjustments cannot be updated.');
                }
            }
        });

        self::deleting(static function (InventoryCostAdjustmentLine $line): void {
            $parent = $line->adjustment ?? InventoryCostAdjustment::query()->find($line->inventory_cost_adjustment_id);
            if ($parent !== null) {
                $status = $parent->status instanceof CostAdjustmentStatus ? $parent->status : CostAdjustmentStatus::from((string) $parent->status);
                if ($status !== CostAdjustmentStatus::Draft) {
                    throw new LogicException('Lines of non-draft inventory cost adjustments cannot be deleted.');
                }
            }
        });
    }

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
