<?php

namespace App\Modules\Procurement\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierInvoice extends BaseModel
{
    protected $table = 'supplier_invoices';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'invoice_number',
        'supplier_id',
        'goods_receipt_id',
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
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'balance_due' => 'decimal:4',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\AccountingPeriod::class, 'period_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'supplier_id');
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Procurement\Models\GoodsReceipt::class, 'goods_receipt_id');
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
