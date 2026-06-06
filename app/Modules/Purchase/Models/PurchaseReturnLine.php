<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class PurchaseReturnLine extends CoreModel
{
    protected $table = 'purchase_return_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'purchase_return_id' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'uom_id' => 'integer',
            'source_line_id' => 'integer',
            'returned_quantity' => 'decimal:6',
            'source_quantity' => 'decimal:6',
            'previously_returned_quantity' => 'decimal:6',
            'remaining_quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'discount_amount' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'charge_amount' => 'decimal:6',
            'line_total' => 'decimal:6',
            'inventory_movement_id' => 'integer',
        ]);
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }
}
