<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceBankStatementLine extends TenantOwnedModel
{
    protected $table = 'finance_bank_statement_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::deleting(function (self $line): void {
            if ($line->matched_ledger_entry_id !== null) {
                throw new LogicException('Matched bank statement lines cannot be deleted.');
            }

            $reconciliation = $line->reconciliation;
            if ($reconciliation && in_array((string) $reconciliation->status, ['completed', 'completed_with_unmatched'], true)) {
                throw new LogicException('Statement lines belonging to completed bank reconciliations cannot be deleted.');
            }
        });

        self::updating(function (self $line): void {
            $reconciliation = $line->reconciliation;
            if ($reconciliation && in_array((string) $reconciliation->status, ['completed', 'completed_with_unmatched'], true)) {
                $allowed = ['updated_at'];
                if (array_diff(array_keys($line->getDirty()), $allowed) !== []) {
                    throw new LogicException('Statement lines belonging to completed bank reconciliations cannot be updated.');
                }
            }
        });
    }

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
