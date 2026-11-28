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
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Enums\StockCountType;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryStockCount extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'inventory_stock_counts';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryStockCount $count): void {
            if ($count->isDirty(['tenant_id', 'count_number'])) {
                throw new LogicException('Structural tenant and count number attributes are immutable.');
            }

            $rawOriginalStatus = $count->getRawOriginal('status');
            $originalStatus = $rawOriginalStatus instanceof StockCountStatus
                ? $rawOriginalStatus
                : StockCountStatus::from((string) $rawOriginalStatus);

            if ($originalStatus === StockCountStatus::Posted) {
                throw new LogicException('Posted inventory stock counts are strictly immutable in place.');
            }
        });

        self::deleting(static function (InventoryStockCount $count): void {
            $status = $count->status instanceof StockCountStatus
                ? $count->status
                : StockCountStatus::from((string) $count->status);

            if ($status !== StockCountStatus::Draft) {
                throw new LogicException('Only draft inventory stock counts can be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'count_date' => 'date',
            'count_type' => StockCountType::class,
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'inventory_adjustment_id' => 'integer',
            'status' => StockCountStatus::class,
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

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryStockCountLine::class, 'inventory_stock_count_id');
    }
}
