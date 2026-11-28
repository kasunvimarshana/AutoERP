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
use Modules\Inventory\Enums\TransferStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class InventoryTransferLine extends TenantOwnedModel
{
    protected $table = 'inventory_transfer_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryTransferLine $line): void {
            if ($line->isDirty(['tenant_id', 'inventory_transfer_id'])) {
                throw new LogicException('Structural keys on inventory transfer lines are immutable.');
            }

            $parent = $line->transfer ?? InventoryTransfer::query()->find($line->inventory_transfer_id);
            if ($parent !== null) {
                $status = $parent->status instanceof TransferStatus ? $parent->status : TransferStatus::from((string) $parent->status);
                if (! in_array($status, [TransferStatus::Draft, TransferStatus::Pending], true)) {
                    if ($line->isDirty(['item_id', 'base_uom_id', 'entered_uom_id', 'quantity', 'conversion_factor'])) {
                        throw new LogicException('Item and planned quantity attributes on dispatched transfers are immutable.');
                    }
                }
            }
        });

        self::deleting(static function (InventoryTransferLine $line): void {
            $parent = $line->transfer ?? InventoryTransfer::query()->find($line->inventory_transfer_id);
            if ($parent !== null) {
                $status = $parent->status instanceof TransferStatus ? $parent->status : TransferStatus::from((string) $parent->status);
                if (! in_array($status, [TransferStatus::Draft, TransferStatus::Pending], true)) {
                    throw new LogicException('Lines of non-draft inventory transfers cannot be deleted.');
                }
            }
        });
    }

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
