<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InventoryMovement extends CoreModel
{
    use SoftDeletes;

    protected $table = 'inventory_movements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'movement_date' => 'date',
            'movement_type' => InventoryMovementType::class,
            'direction' => InventoryDirection::class,
            'item_id' => 'integer',
            'base_uom_id' => 'integer',
            'item_variant_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'batch_id' => 'integer',
            'serial_number_id' => 'integer',
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:6',
            'balance_quantity_after' => 'decimal:6',
            'balance_value_after' => 'decimal:6',
            'source_id' => 'integer',
            'source_line_id' => 'integer',
            'status' => InventoryStatus::class,
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
            'reversal_of_id' => 'integer',
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

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(InventorySerialNumber::class, 'serial_number_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    public function valuationLayers(): HasMany
    {
        return $this->hasMany(InventoryValuationLayer::class, 'movement_id');
    }
}
