<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventorySerialNumber extends CoreModel
{
    use SoftDeletes;

    protected $table = 'inventory_serial_numbers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'batch_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'source_id' => 'integer',
            'status' => SerialStatus::class,
            'metadata' => 'array',
        ]);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'warehouse_location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'serial_number_id');
    }
}
