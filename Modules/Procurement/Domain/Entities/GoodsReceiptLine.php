<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * GoodsReceiptLine entity.
 *
 * All quantity and cost values are cast to string for BCMath precision.
 */
class GoodsReceiptLine extends Model
{
    use HasTenant;

    protected $table = 'goods_receipt_lines';

    protected $fillable = [
        'tenant_id',
        'goods_receipt_id',
        'purchase_order_line_id',
        'quantity_received',
        'unit_cost',
    ];

    protected $casts = [
        'goods_receipt_id'       => 'integer',
        'purchase_order_line_id' => 'integer',
        'quantity_received'      => 'string',
        'unit_cost'              => 'string',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }
}
