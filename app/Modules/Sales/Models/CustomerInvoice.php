<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerInvoice extends BaseModel
{
    protected $table = 'customer_invoices';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'invoice_number',
        'customer_id',
        'sales_order_id',
        'delivery_id',
        'invoice_date',
        'due_date',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'total',
        'paid_amount',
        'balance_due',
        'status',
        'journal_entry_id',
        'created_by'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'balance_due' => 'decimal:4'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\AccountingPeriod::class, 'period_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'customer_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\SalesOrder::class, 'sales_order_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Sales\Models\DeliveryOrder::class, 'delivery_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'currency_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Finance\Models\JournalEntry::class, 'journal_entry_id');
    }
}
