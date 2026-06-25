<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Sales\Enums\SalesOrderLineStatus;
use Modules\UOM\Models\UnitOfMeasureModel;

final class SalesOrderLine extends CoreModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status' => SalesOrderLineStatus::class,
            'uom_conversion_factor' => 'decimal:6',
            'ordered_quantity' => 'decimal:6',
            'base_quantity' => 'decimal:6',
            'allocated_quantity' => 'decimal:6',
            'delivered_quantity' => 'decimal:6',
            'invoiced_quantity' => 'decimal:6',
            'returned_quantity' => 'decimal:6',
            'cancelled_quantity' => 'decimal:6',
            'remaining_allocatable_quantity' => 'decimal:6',
            'remaining_deliverable_quantity' => 'decimal:6',
            'remaining_invoiceable_quantity' => 'decimal:6',
            'remaining_returnable_quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'line_subtotal' => 'decimal:6',
            'discount_rate' => 'decimal:6',
            'discount_amount' => 'decimal:6',
            'tax_rate' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'charge_rate' => 'decimal:6',
            'charge_amount' => 'decimal:6',
            'line_total' => 'decimal:6',
        ]);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function quotationLine(): BelongsTo
    {
        return $this->belongsTo(SalesQuotationLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function orderedUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'ordered_uom_id');
    }

    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'base_uom_id');
    }

    public function inventoryAllocation(): BelongsTo
    {
        return $this->belongsTo(InventoryAllocation::class);
    }
}
