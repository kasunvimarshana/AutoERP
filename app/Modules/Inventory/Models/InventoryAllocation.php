<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryAllocation extends CoreModel
{
    use SoftDeletes;

    protected $table = 'inventory_allocations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'allocation_date' => 'date',
            'allocation_method' => AllocationMethod::class,
            'reservation_id' => 'integer',
            'item_id' => 'integer',
            'base_uom_id' => 'integer',
            'item_variant_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'batch_id' => 'integer',
            'serial_number_id' => 'integer',
            'quantity_allocated' => 'decimal:6',
            'quantity_issued' => 'decimal:6',
            'quantity_reversed' => 'decimal:6',
            'quantity_released' => 'decimal:6',
            'quantity_remaining' => 'decimal:6',
            'source_id' => 'integer',
            'source_line_id' => 'integer',
            'status' => AllocationStatus::class,
        ]);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class, 'reservation_id');
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

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryAllocationLine::class, 'allocation_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(InventoryAllocationIssue::class, 'allocation_id');
    }
}
