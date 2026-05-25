<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceLineModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class InvoiceModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, SoftDeletes;

    protected $table = 'invoices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'credit_note_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'due_date' => 'date',
            'exchange_rate' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_tax_amount' => 'decimal:4',
            'invoice_date' => 'date',
            'line_discount_total' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'metadata' => 'array',
            'paid_amount' => 'decimal:4',
            'party_id' => 'integer',
            'row_version' => 'integer',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
        ];
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'ap_account_id');
    }

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'ar_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function headerTaxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'header_tax_group_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLineModel::class, 'invoice_id');
    }

    public function invoiceReferences(): HasMany
    {
        return $this->hasMany(InvoiceReferenceModel::class, 'invoice_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturnModel::class, 'original_invoice_id');
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturnModel::class, 'original_invoice_id');
    }

}
