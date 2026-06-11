<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\CoreModel;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class SalesQuotationLine extends CoreModel
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'quantity' => 'decimal:6',
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
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
}
