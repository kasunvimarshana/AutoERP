<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PurchaseInvoiceLineModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'purchase_invoice_lines';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'purchase_invoice_id',
        'grn_line_id',
        'product_id',
        'variant_id',
        'description',
        'uom_id',
        'quantity',
        'unit_price',
        'discount_pct',
        'tax_group_id',
        'tax_amount',
        'line_total',
        'account_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'purchase_invoice_id' => 'integer',
        'grn_line_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'uom_id' => 'integer',
        'tax_group_id' => 'integer',
        'account_id' => 'integer',
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_pct' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'line_total' => 'decimal:6',
    ];
}
