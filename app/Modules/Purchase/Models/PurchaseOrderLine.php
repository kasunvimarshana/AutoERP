<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\UOM\Models\UnitOfMeasureModel;

final class PurchaseOrderLine extends TenantOwnedModel
{
    protected $table = 'purchase_order_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'purchase_order_id' => 'integer',
            'line_number' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'uom_id' => 'integer',
            'ordered_uom_id' => 'integer',
            'base_uom_id' => 'integer',
            'uom_conversion_factor' => 'decimal:6',
            'ordered_quantity' => 'decimal:6',
            'base_quantity' => 'decimal:6',
            'received_quantity' => 'decimal:6',
            'invoiced_quantity' => 'decimal:6',
            'returned_quantity' => 'decimal:6',
            'cancelled_quantity' => 'decimal:6',
            'remaining_quantity' => 'decimal:6',
            'remaining_receivable_quantity' => 'decimal:6',
            'remaining_invoiceable_quantity' => 'decimal:6',
            'remaining_returnable_quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'line_subtotal' => 'decimal:6',
            'discount_rate' => 'decimal:6',
            'discount_amount' => 'decimal:6',
            'tax_group_id' => 'integer',
            'tax_rate' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'charge_rate' => 'decimal:6',
            'charge_amount' => 'decimal:6',
            'line_total' => 'decimal:6',
            'status' => PurchaseOrderLineStatus::class,
        ]);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
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
}
