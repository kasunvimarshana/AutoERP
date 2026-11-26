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
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceBankReconciliation extends TenantOwnedModel
{
    protected $table = 'finance_bank_reconciliations';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::deleting(function (self $reconciliation): void {
            if (in_array((string) $reconciliation->status, ['completed', 'completed_with_unmatched'], true)) {
                throw new LogicException('Completed bank reconciliations cannot be deleted.');
            }
        });

        self::updating(function (self $reconciliation): void {
            $originalStatus = (string) $reconciliation->getOriginal('status');
            if (in_array($originalStatus, ['completed', 'completed_with_unmatched'], true)) {
                $allowed = ['updated_at'];
                if (array_diff(array_keys($reconciliation->getDirty()), $allowed) !== []) {
                    throw new LogicException('Completed bank reconciliations are immutable.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'bank_account_id' => 'integer',
            'statement_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'opening_balance' => 'decimal:6',
            'closing_balance' => 'decimal:6',
            'reconciled_balance' => 'decimal:6',
            'completed_by' => 'integer',
            'completed_at' => 'datetime',
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'bank_account_id');
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(FinanceBankStatementLine::class, 'reconciliation_id');
    }
}
