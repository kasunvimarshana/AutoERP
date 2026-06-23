<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryStockBalance extends TenantOwnedModel
{
    protected $table = 'inventory_stock_balances';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_id' => 'integer',
            'base_uom_id' => 'integer',
            'item_variant_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'batch_id' => 'integer',
            'quantity_on_hand' => 'decimal:6',
            'quantity_reserved' => 'decimal:6',
            'quantity_allocated' => 'decimal:6',
            'quantity_available' => 'decimal:6',
            'quantity_returned' => 'decimal:6',
            'quantity_in_transit' => 'decimal:6',
            'quantity_damaged' => 'decimal:6',
            'quantity_quarantine' => 'decimal:6',
            'quantity_expired' => 'decimal:6',
            'quantity_scrapped' => 'decimal:6',
            'average_cost' => 'decimal:6',
            'total_value' => 'decimal:6',
        ]);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'base_uom_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'warehouse_location_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }
}
