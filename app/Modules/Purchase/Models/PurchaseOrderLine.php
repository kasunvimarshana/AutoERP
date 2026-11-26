<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\UOM\Models\UnitOfMeasureModel;

final class PurchaseOrderLine extends TenantOwnedModel
{
    protected $table = 'purchase_order_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (PurchaseOrderLine $line): void {
            $order = $line->order()->first();
            if ($order !== null && $order->status !== PurchaseOrderStatus::Draft) {
                $immutable = ['item_id', 'item_variant_id', 'ordered_quantity', 'unit_price', 'ordered_uom_id', 'base_uom_id', 'uom_conversion_factor', 'base_quantity', 'tenant_id', 'organization_unit_id'];
                if ($line->isDirty($immutable)) {
                    throw new LogicException('Lines of non-draft purchase orders are immutable in place.');
                }
            }
        });

        self::deleting(static function (PurchaseOrderLine $line): void {
            $order = $line->order()->first();
            if ($order !== null && $order->status !== PurchaseOrderStatus::Draft) {
                throw new LogicException('Lines of non-draft purchase orders cannot be deleted.');
            }
        });
    }

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
