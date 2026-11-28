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
use Modules\Inventory\Enums\BatchStatus;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;

final class InventoryBatch extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'inventory_batches';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (InventoryBatch $batch): void {
            if ($batch->isDirty(['tenant_id', 'item_id', 'batch_number'])) {
                throw new LogicException('Structural tenant, item, and batch number attributes are immutable.');
            }
        });

        self::deleting(static function (InventoryBatch $batch): void {
            $hasStock = InventoryStockBalance::query()
                ->where('batch_id', $batch->getKey())
                ->where('quantity_on_hand', '>', 0)
                ->exists();
            if ($hasStock) {
                throw new LogicException('Inventory batches with positive stock on hand cannot be deleted.');
            }

            $hasMovements = InventoryMovement::query()
                ->where('batch_id', $batch->getKey())
                ->exists();
            if ($hasMovements) {
                throw new LogicException('Inventory batches with historical movements cannot be deleted.');
            }

            $hasActiveAllocations = InventoryAllocationLine::query()
                ->where('batch_id', $batch->getKey())
                ->where('quantity_remaining', '>', 0)
                ->exists();
            if ($hasActiveAllocations) {
                throw new LogicException('Inventory batches with active allocations cannot be deleted.');
            }

            $hasActiveReservations = InventoryReservation::query()
                ->where('batch_id', $batch->getKey())
                ->where('quantity_remaining', '>', 0)
                ->exists();
            if ($hasActiveReservations) {
                throw new LogicException('Inventory batches with active reservations cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'status' => BatchStatus::class,
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

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(InventorySerialNumber::class, 'batch_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(InventoryStockBalance::class, 'batch_id');
    }

    public function priceRevisions(): HasMany
    {
        return $this->hasMany(InventoryBatchPriceRevision::class, 'batch_id');
    }
}
