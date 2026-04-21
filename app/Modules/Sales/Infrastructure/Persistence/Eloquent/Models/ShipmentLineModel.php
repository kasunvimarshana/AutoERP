<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentLineModel extends Model
{
    protected $table = 'shipment_lines';

    protected $fillable = [
        'tenant_id',
        'shipment_id',
        'sales_order_line_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'from_location_id',
        'uom_id',
        'shipped_qty',
        'unit_cost',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'shipment_id' => 'integer',
        'sales_order_line_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'from_location_id' => 'integer',
        'uom_id' => 'integer',
        'shipped_qty' => 'decimal:6',
        'unit_cost' => 'decimal:6',
    ];
}
