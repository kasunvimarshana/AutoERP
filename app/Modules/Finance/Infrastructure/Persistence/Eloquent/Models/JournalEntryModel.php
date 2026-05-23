<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use App\Support\Eloquent\Concerns\HasOrganizationUnitScope;
use App\Support\Eloquent\Concerns\HasReferenceScope;
use App\Support\Eloquent\Concerns\HasStatusScope;
use App\Support\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayslipModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\WriteOffModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class JournalEntryModel extends Model
{
    use HasOrganizationUnitScope, HasReferenceScope, HasStatusScope, HasTenantScope;

    protected $table = 'journal_entries';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'entry_number';

    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'entry_date' => 'date',
            'fiscal_period_id' => 'integer',
            'is_reversed' => 'boolean',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'posted_at' => 'datetime',
            'posted_by' => 'integer',
            'posting_date' => 'date',
            'reference_id' => 'integer',
            'reversal_entry_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriodModel::class, 'fiscal_period_id');
    }

    public function reversalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'reversal_entry_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntryModel::class, 'reversal_entry_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLineModel::class, 'journal_entry_id');
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransactionModel::class, 'matched_journal_entry_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(PayslipModel::class, 'journal_entry_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceModel::class, 'journal_entry_id');
    }

    public function invoiceReferences(): HasMany
    {
        return $this->hasMany(InvoiceReferenceModel::class, 'journal_entry_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentModel::class, 'journal_entry_id');
    }

    public function writeOffs(): HasMany
    {
        return $this->hasMany(WriteOffModel::class, 'journal_entry_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
