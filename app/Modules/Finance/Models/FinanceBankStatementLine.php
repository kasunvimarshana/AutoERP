<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceBankStatementLine extends TenantOwnedModel
{
    protected $table = 'finance_bank_statement_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'reconciliation_id' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'bank_account_id' => 'integer',
            'statement_date' => 'date',
            'debit' => 'decimal:6',
            'credit' => 'decimal:6',
            'matched_ledger_entry_id' => 'integer',
            'matched_at' => 'datetime',
        ]);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(FinanceBankReconciliation::class, 'reconciliation_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'bank_account_id');
    }

    public function matchedLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(FinanceLedgerEntry::class, 'matched_ledger_entry_id');
    }
}
