<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class GoodsReceiptLineModel extends BaseModel
{
    use HasAudit;

    protected $table = 'goods_receipt_lines';

    protected $fillable = [
        'tenant_id', 'goods_receipt_id', 'line_number', 'purchase_order_line_id',
        'product_id', 'variation_id', 'batch_id', 'serial_number', 'uom_id',
        'quantity_expected', 'quantity_received', 'quantity_accepted', 'quantity_rejected',
        'unit_cost', 'condition', 'notes', 'metadata', 'status', 'putaway_location_id',
    ];

    protected $casts = [
        'tenant_id'            => 'integer',
        'goods_receipt_id'     => 'integer',
        'line_number'          => 'integer',
        'purchase_order_line_id' => 'integer',
        'product_id'           => 'integer',
        'variation_id'         => 'integer',
        'batch_id'             => 'integer',
        'uom_id'               => 'integer',
        'quantity_expected'    => 'float',
        'quantity_received'    => 'float',
        'quantity_accepted'    => 'float',
        'quantity_rejected'    => 'float',
        'unit_cost'            => 'float',
        'putaway_location_id'  => 'integer',
        'metadata'             => 'array',
    ];
}
