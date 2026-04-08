<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class SalesOrderModel extends BaseModel
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id', 'reference_number', 'status', 'customer_id', 'customer_reference',
        'order_date', 'required_date', 'warehouse_id', 'currency', 'subtotal',
        'tax_amount', 'discount_amount', 'total_amount', 'shipping_address', 'notes',
        'metadata', 'confirmed_by', 'confirmed_at', 'shipped_by', 'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'tenant_id'       => 'integer',
        'customer_id'     => 'integer',
        'warehouse_id'    => 'integer',
        'confirmed_by'    => 'integer',
        'shipped_by'      => 'integer',
        'subtotal'        => 'float',
        'tax_amount'      => 'float',
        'discount_amount' => 'float',
        'total_amount'    => 'float',
        'shipping_address' => 'array',
        'metadata'        => 'array',
        'confirmed_at'    => 'datetime',
        'shipped_at'      => 'datetime',
        'delivered_at'    => 'datetime',
    ];
}
