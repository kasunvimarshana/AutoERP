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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryAdjustment extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'inventory_adjustments';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryAdjustment $adjustment): void {
            if ($adjustment->isDirty(['tenant_id', 'adjustment_number'])) {
                throw new LogicException('Structural tenant and adjustment number attributes are immutable.');
            }

            $rawOriginalStatus = $adjustment->getRawOriginal('status');
            $originalStatus = $rawOriginalStatus instanceof AdjustmentStatus
                ? $rawOriginalStatus
                : AdjustmentStatus::from((string) $rawOriginalStatus);

            $rawNewStatus = $adjustment->getAttribute('status');
            $newStatus = $rawNewStatus instanceof AdjustmentStatus
                ? $rawNewStatus
                : AdjustmentStatus::from((string) $rawNewStatus);

            $isReversal = $originalStatus === AdjustmentStatus::Posted
                && $newStatus === AdjustmentStatus::Reversed
                && ! $adjustment->isDirty([
                    'tenant_id',
                    'organization_unit_id',
                    'adjustment_type',
                    'warehouse_id',
                    'warehouse_location_id',
                    'adjustment_date',
                    'adjustment_number',
                ]);

            if (in_array($originalStatus, [AdjustmentStatus::Posted, AdjustmentStatus::Reversed, AdjustmentStatus::Cancelled], true) && ! $isReversal) {
                throw new LogicException('Posted, reversed, or cancelled inventory adjustments are immutable in place.');
            }

            if ($originalStatus === AdjustmentStatus::Approved
                && $newStatus !== AdjustmentStatus::Posted
                && $newStatus !== AdjustmentStatus::Draft
                && $adjustment->isDirty(['warehouse_id', 'warehouse_location_id', 'adjustment_type', 'adjustment_date'])) {
                throw new LogicException('Approved inventory adjustments cannot have core warehouse or date attributes modified.');
            }
        });

        self::deleting(static function (InventoryAdjustment $adjustment): void {
            $status = $adjustment->status instanceof AdjustmentStatus
                ? $adjustment->status
                : AdjustmentStatus::from((string) $adjustment->status);

            if ($status !== AdjustmentStatus::Draft) {
                throw new LogicException('Only draft inventory adjustments can be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'adjustment_date' => 'date',
            'adjustment_type' => AdjustmentType::class,
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'status' => AdjustmentStatus::class,
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ]);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'warehouse_location_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentLine::class, 'inventory_adjustment_id');
    }
}
