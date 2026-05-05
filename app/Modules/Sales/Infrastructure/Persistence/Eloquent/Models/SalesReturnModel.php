<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class SalesReturnModel extends Model
{
    use HasAudit, HasTenant;

    protected $table = 'sales_returns';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'customer_id',
        'original_sales_order_id',
        'original_invoice_id',
        'return_number',
        'status',
        'return_date',
        'return_reason',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'restocking_fee_total',
        'grand_total',
        'credit_memo_number',
        'journal_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'customer_id' => 'integer',
        'original_sales_order_id' => 'integer',
        'original_invoice_id' => 'integer',
        'currency_id' => 'integer',
        'journal_entry_id' => 'integer',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'restocking_fee_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'return_date' => 'date',
        'metadata' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReturnLineModel::class, 'sales_return_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function originalSalesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrderModel::class, 'original_sales_order_id');
    }

    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceModel::class, 'original_invoice_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }
}
