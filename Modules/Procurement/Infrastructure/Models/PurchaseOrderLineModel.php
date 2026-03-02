<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLineModel extends Model
{
    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'tax_rate',
        'discount_rate',
        'line_total',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderModel::class, 'purchase_order_id');
    }
}
