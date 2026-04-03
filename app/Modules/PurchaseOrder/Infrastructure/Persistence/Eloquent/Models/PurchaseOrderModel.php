<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PurchaseOrderModel extends BaseModel
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id', 'reference_number', 'status', 'supplier_id', 'supplier_reference',
        'order_date', 'expected_date', 'warehouse_id', 'currency', 'subtotal',
        'tax_amount', 'discount_amount', 'total_amount', 'notes', 'metadata',
        'approved_by', 'approved_at', 'submitted_by', 'submitted_at',
    ];

    protected $casts = [
        'tenant_id'       => 'integer',
        'supplier_id'     => 'integer',
        'warehouse_id'    => 'integer',
        'approved_by'     => 'integer',
        'submitted_by'    => 'integer',
        'subtotal'        => 'float',
        'tax_amount'      => 'float',
        'discount_amount' => 'float',
        'total_amount'    => 'float',
        'metadata'        => 'array',
        'approved_at'     => 'datetime',
        'submitted_at'    => 'datetime',
    ];
}
