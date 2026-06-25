<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Sales\Enums\SalesAllocationStatus;
use Modules\UOM\Models\UnitOfMeasureModel;

final class SalesAllocationLine extends CoreModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'requested_quantity' => 'decimal:6',
            'allocated_quantity' => 'decimal:6',
            'released_quantity' => 'decimal:6',
            'issued_quantity' => 'decimal:6',
            'status' => SalesAllocationStatus::class,
        ]);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(SalesAllocation::class, 'sales_allocation_id');
    }

    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function inventoryAllocation(): BelongsTo
    {
        return $this->belongsTo(InventoryAllocation::class);
    }
}
