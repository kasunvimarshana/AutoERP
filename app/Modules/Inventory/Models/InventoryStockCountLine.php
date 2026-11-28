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
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InventoryStockCountLine extends TenantOwnedModel
{
    protected $table = 'inventory_stock_count_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryStockCountLine $line): void {
            if ($line->isDirty(['tenant_id', 'inventory_stock_count_id'])) {
                throw new LogicException('Structural keys on inventory stock count lines are immutable.');
            }

            $parent = $line->stockCount ?? InventoryStockCount::query()->find($line->inventory_stock_count_id);
            if ($parent !== null) {
                $status = $parent->status instanceof StockCountStatus ? $parent->status : StockCountStatus::from((string) $parent->status);
                if ($status === StockCountStatus::Posted) {
                    throw new LogicException('Lines of posted inventory stock counts are strictly immutable.');
                }
                if ($status === StockCountStatus::Approved) {
                    $dirtyKeys = array_keys($line->getDirty());
                    $allowedKeys = ['inventory_adjustment_line_id', 'updated_at'];
                    if (array_diff($dirtyKeys, $allowedKeys) !== []) {
                        throw new LogicException('Lines of approved inventory stock counts cannot be updated.');
                    }
                }
            }
        });

        self::deleting(static function (InventoryStockCountLine $line): void {
            $parent = $line->stockCount ?? InventoryStockCount::query()->find($line->inventory_stock_count_id);
            if ($parent !== null) {
                $status = $parent->status instanceof StockCountStatus ? $parent->status : StockCountStatus::from((string) $parent->status);
                if ($status !== StockCountStatus::Draft) {
                    throw new LogicException('Lines of non-draft inventory stock counts cannot be deleted.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'inventory_stock_count_id' => 'integer',
            'item_id' => 'integer',
            'base_uom_id' => 'integer',
            'entered_uom_id' => 'integer',
            'item_variant_id' => 'integer',
            'batch_id' => 'integer',
            'serial_number_id' => 'integer',
            'entered_system_quantity' => 'decimal:6',
            'entered_counted_quantity' => 'decimal:6',
            'entered_unit_cost' => 'decimal:6',
            'conversion_factor' => 'decimal:6',
            'system_quantity' => 'decimal:6',
            'counted_quantity' => 'decimal:6',
            'variance_quantity' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'inventory_adjustment_line_id' => 'integer',
        ]);
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(InventoryStockCount::class, 'inventory_stock_count_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'base_uom_id');
    }

    public function enteredUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'entered_uom_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(InventorySerialNumber::class, 'serial_number_id');
    }

    public function adjustmentLine(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustmentLine::class, 'inventory_adjustment_line_id');
    }
}
