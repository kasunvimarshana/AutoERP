<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class SalesDeliveryLine extends CoreModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'ordered_quantity' => 'decimal:6',
            'delivered_quantity' => 'decimal:6',
            'invoiced_quantity' => 'decimal:6',
            'returned_quantity' => 'decimal:6',
            'remaining_quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'line_total' => 'decimal:6',
        ]);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(SalesDelivery::class, 'sales_delivery_id');
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
        return $this->belongsTo(UnitOfMeasureModel::class);
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class);
    }
}
