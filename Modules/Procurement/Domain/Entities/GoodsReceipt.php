<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * GoodsReceipt entity.
 *
 * Represents physical receipt of goods against a purchase order.
 */
class GoodsReceipt extends Model
{
    use HasTenant;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'receipt_number',
        'status',
        'received_at',
        'warehouse_id',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'warehouse_id'      => 'integer',
        'received_at'       => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptLine::class, 'goods_receipt_id');
    }
}
