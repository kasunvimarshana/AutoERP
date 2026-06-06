<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\Finance\Enums\JournalStatus;
use Modules\Finance\Enums\JournalType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceJournalEntry extends CoreModel
{
    use SoftDeletes;

    protected $table = 'finance_journal_entries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'fiscal_year_id' => 'integer',
            'fiscal_period_id' => 'integer',
            'source_id' => 'integer',
            'journal_type' => JournalType::class,
            'status' => JournalStatus::class,
            'journal_date' => 'date',
            'total_debit' => 'decimal:6',
            'total_credit' => 'decimal:6',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:6',
            'created_by' => 'integer',
            'posted_by' => 'integer',
            'posted_at' => 'datetime',
            'reversed_by' => 'integer',
            'reversed_at' => 'datetime',
            'reversal_of_id' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FinanceFiscalYear::class, 'fiscal_year_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FinanceFiscalPeriod::class, 'fiscal_period_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceJournalLine::class, 'journal_entry_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(FinanceLedgerEntry::class, 'journal_entry_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }
}
