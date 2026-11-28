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

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Enums\CostAdjustmentStatus;

final class InventoryCostAdjustment extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'inventory_cost_adjustments';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryCostAdjustment $adjustment): void {
            if ($adjustment->isDirty(['tenant_id', 'adjustment_number'])) {
                throw new LogicException('Structural tenant and adjustment number attributes are immutable.');
            }

            $rawOriginalStatus = $adjustment->getRawOriginal('status');
            $originalStatus = $rawOriginalStatus instanceof CostAdjustmentStatus
                ? $rawOriginalStatus
                : CostAdjustmentStatus::from((string) $rawOriginalStatus);

            if ($originalStatus === CostAdjustmentStatus::Posted) {
                throw new LogicException('Posted inventory cost adjustments are strictly immutable in place.');
            }
        });

        self::deleting(static function (InventoryCostAdjustment $adjustment): void {
            $status = $adjustment->status instanceof CostAdjustmentStatus
                ? $adjustment->status
                : CostAdjustmentStatus::from((string) $adjustment->status);

            if ($status !== CostAdjustmentStatus::Draft) {
                throw new LogicException('Only draft inventory cost adjustments can be deleted.');
            }
        });
    }

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
