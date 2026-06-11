<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryStockCount extends CoreModel
{
    use SoftDeletes;

    protected $table = 'inventory_stock_counts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'count_date' => 'date',
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
