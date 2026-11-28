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
use Modules\Inventory\Enums\SerialStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventorySerialNumber extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'inventory_serial_numbers';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventorySerialNumber $serial): void {
            if ($serial->isDirty(['tenant_id', 'item_id', 'serial_number'])) {
                throw new LogicException('Structural tenant, item, and serial number attributes are immutable.');
            }
        });

        self::deleting(static function (InventorySerialNumber $serial): void {
            $status = $serial->status instanceof SerialStatus
                ? $serial->status
                : SerialStatus::from((string) $serial->status);

            if (in_array($status, [SerialStatus::Reserved, SerialStatus::Issued, SerialStatus::Sold], true)) {
                throw new LogicException('Inventory serial numbers in active or historical usage cannot be deleted.');
            }

            $hasMovements = InventoryMovement::query()
                ->where('serial_number_id', $serial->getKey())
                ->exists();
            if ($hasMovements) {
                throw new LogicException('Inventory serial numbers with historical movements cannot be deleted.');
            }

            $hasAllocations = InventoryAllocationLine::query()
                ->where('serial_number_id', $serial->getKey())
                ->where('quantity_remaining', '>', 0)
                ->exists();
            if ($hasAllocations) {
                throw new LogicException('Inventory serial numbers with active allocations cannot be deleted.');
            }
        });
    }

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
