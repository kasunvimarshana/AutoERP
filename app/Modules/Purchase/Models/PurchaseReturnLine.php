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
use Modules\Inventory\Models\InventoryMovement;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\UOM\Models\UnitOfMeasureModel;

final class PurchaseReturnLine extends TenantOwnedModel
{
    protected $table = 'purchase_return_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (PurchaseReturnLine $line): void {
            $return = $line->purchaseReturn()->first();
            if ($return !== null && $return->status !== PurchaseReturnStatus::Draft) {
                $immutable = ['item_id', 'item_variant_id', 'returned_quantity', 'unit_price', 'uom_id', 'source_line_id', 'line_total', 'tenant_id', 'organization_unit_id'];
                if ($line->isDirty($immutable)) {
                    throw new LogicException('Lines of non-draft purchase returns are immutable in place.');
                }
            }
        });

        self::deleting(static function (PurchaseReturnLine $line): void {
            $return = $line->purchaseReturn()->first();
            if ($return !== null && $return->status !== PurchaseReturnStatus::Draft) {
                throw new LogicException('Lines of non-draft purchase returns cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'purchase_return_id' => 'integer',
            'line_number' => 'integer',
            'item_id' => 'integer',
            'item_variant_id' => 'integer',
            'uom_id' => 'integer',
            'source_line_id' => 'integer',
            'returned_quantity' => 'decimal:6',
            'source_quantity' => 'decimal:6',
            'previously_returned_quantity' => 'decimal:6',
            'remaining_quantity' => 'decimal:6',
            'unit_price' => 'decimal:6',
            'cost_basis' => 'decimal:6',
            'base_amount' => 'decimal:6',
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
