<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InventoryTransferLine extends CoreModel
{
    protected $table = 'inventory_transfer_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'inventory_transfer_id' => 'integer',
            'item_id' => 'integer',
            'base_uom_id' => 'integer',
            'entered_uom_id' => 'integer',
            'item_variant_id' => 'integer',
            'batch_id' => 'integer',
            'serial_number_id' => 'integer',
            'entered_quantity' => 'decimal:6',
            'entered_unit_cost' => 'decimal:6',
            'conversion_factor' => 'decimal:6',
            'quantity' => 'decimal:6',
            'dispatched_quantity' => 'decimal:6',
            'received_quantity' => 'decimal:6',
            'cancelled_quantity' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:6',
            'outbound_movement_id' => 'integer',
            'inbound_movement_id' => 'integer',
        ]);
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
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

    public function outboundMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'outbound_movement_id');
    }

    public function inboundMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inbound_movement_id');
    }
}
