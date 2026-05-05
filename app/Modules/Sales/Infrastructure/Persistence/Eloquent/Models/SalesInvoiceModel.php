<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SalesInvoiceModel extends Model
{
    use HasAudit, HasTenant;

    protected $table = 'sales_invoices';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sales_order_id',
        'shipment_id',
        'invoice_number',
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
        'ar_account_id',
        'journal_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'customer_id' => 'integer',
        'sales_order_id' => 'integer',
        'shipment_id' => 'integer',
        'currency_id' => 'integer',
        'ar_account_id' => 'integer',
        'journal_entry_id' => 'integer',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'discount_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'paid_amount' => 'decimal:6',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLineModel::class, 'sales_invoice_id');
    }
}
