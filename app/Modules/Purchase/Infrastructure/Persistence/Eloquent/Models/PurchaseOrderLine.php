<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class PurchaseOrderLine extends CoreModel
{
    use SoftDeletes;

    protected $table = 'purchase_order_lines';

    protected $guarded = ['id'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLine::class, 'purchase_order_line_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'purchase_order_id' => 'integer',
            'item_id' => 'integer',
            'variant_id' => 'integer',
            'uom_id' => 'integer',
            'ordered_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'returned_qty' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'tax_group_id' => 'integer',
            'tax_amount' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'account_id' => 'integer',
        ]);
    }
}
