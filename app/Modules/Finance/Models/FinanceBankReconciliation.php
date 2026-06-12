<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceBankReconciliation extends CoreModel
{
    protected $table = 'finance_bank_reconciliations';

    protected $guarded = ['id'];

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
