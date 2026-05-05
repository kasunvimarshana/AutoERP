<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PurchaseReturnModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'supplier_id',
        'original_grn_id',
        'original_invoice_id',
        'return_number',
        'status',
        'return_date',
        'return_reason',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'grand_total',
            'discount_total',
        'debit_note_number',
        'journal_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'supplier_id' => 'integer',
        'original_grn_id' => 'integer',
        'original_invoice_id' => 'integer',
        'currency_id' => 'integer',
        'journal_entry_id' => 'integer',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
            'discount_total' => 'decimal:6',
        'return_date' => 'date',
        'metadata' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function originalGrn(): BelongsTo
    {
        return $this->belongsTo(GrnHeaderModel::class, 'original_grn_id');
    }

    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceModel::class, 'original_invoice_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'purchase_return_id');
    }
}
