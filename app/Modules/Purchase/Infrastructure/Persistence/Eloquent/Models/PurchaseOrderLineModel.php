<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PurchaseOrderLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'product_id',
        'variant_id',
        'description',
        'uom_id',
        'ordered_qty',
        'received_qty',
        'unit_price',
        'discount_pct',
        'tax_group_id',
        'account_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'purchase_order_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'uom_id' => 'integer',
        'tax_group_id' => 'integer',
        'account_id' => 'integer',
        'ordered_qty' => 'decimal:6',
        'received_qty' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_pct' => 'decimal:6',
        'line_total' => 'decimal:6',
    ];
}
