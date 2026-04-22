<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PurchaseInvoiceModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'purchase_invoices';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'grn_header_id',
        'purchase_order_id',
        'invoice_number',
        'supplier_invoice_number',
        'status',
        'invoice_date',
        'due_date',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'paid_amount',
        'ap_account_id',
        'journal_entry_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'supplier_id' => 'integer',
        'grn_header_id' => 'integer',
        'purchase_order_id' => 'integer',
        'currency_id' => 'integer',
        'ap_account_id' => 'integer',
        'journal_entry_id' => 'integer',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'discount_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'paid_amount' => 'decimal:6',
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];
}
