<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'entry_date' => 'date',
            'debit' => 'decimal:6',
            'credit' => 'decimal:6',
            'balance_after' => 'decimal:6',
            'source_id' => 'integer',
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
}
