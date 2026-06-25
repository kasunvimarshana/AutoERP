<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceLedgerEntry extends CoreModel
{
    protected $table = 'finance_ledger_entries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'journal_entry_id' => 'integer',
            'journal_line_id' => 'integer',
            'account_id' => 'integer',
            'fiscal_year_id' => 'integer',
            'fiscal_period_id' => 'integer',
            'dimension_id' => 'integer',
            'entry_date' => 'date',
            'debit' => 'decimal:6',
            'credit' => 'decimal:6',
            'balance_after' => 'decimal:6',
            'source_id' => 'integer',
            'source_date' => 'date',
            'source_line_id' => 'integer',
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

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(FinanceJournalEntry::class, 'journal_entry_id');
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(FinanceJournalLine::class, 'journal_line_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FinanceFiscalYear::class, 'fiscal_year_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FinanceFiscalPeriod::class, 'fiscal_period_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(FinanceDimension::class, 'dimension_id');
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Ledger entries are immutable.');
        });

        self::deleting(static function (): never {
            throw new LogicException('Ledger entries are immutable.');
        });
    }
}
