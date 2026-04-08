<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class GoodsReceiptModel extends BaseModel
{
    use HasAudit;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'tenant_id', 'reference_number', 'status', 'purchase_order_id', 'supplier_id',
        'warehouse_id', 'received_date', 'currency', 'notes', 'metadata',
        'received_by', 'approved_by', 'approved_at', 'put_away_by', 'inspected_by', 'inspected_at',
    ];

    protected $casts = [
        'tenant_id'         => 'integer',
        'purchase_order_id' => 'integer',
        'supplier_id'       => 'integer',
        'warehouse_id'      => 'integer',
        'received_date'     => 'date',
        'received_by'       => 'integer',
        'approved_by'       => 'integer',
        'approved_at'       => 'datetime',
        'put_away_by'       => 'integer',
        'inspected_by'      => 'integer',
        'inspected_at'      => 'datetime',
        'metadata'          => 'array',
    ];
}
