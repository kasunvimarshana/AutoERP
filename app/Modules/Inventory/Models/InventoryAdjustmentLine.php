<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InventoryAdjustmentLine extends CoreModel
{
    protected $table = 'inventory_adjustment_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'inventory_adjustment_id' => 'integer',
            'item_id' => 'integer',
            'base_uom_id' => 'integer',
            'entered_uom_id' => 'integer',
            'item_variant_id' => 'integer',
            'batch_id' => 'integer',
            'serial_number_id' => 'integer',
            'entered_system_quantity' => 'decimal:6',
            'entered_counted_quantity' => 'decimal:6',
            'entered_adjustment_quantity' => 'decimal:6',
            'entered_unit_cost' => 'decimal:6',
            'conversion_factor' => 'decimal:6',
            'system_quantity' => 'decimal:6',
            'counted_quantity' => 'decimal:6',
            'adjustment_quantity' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:6',
        ]);
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
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
}
