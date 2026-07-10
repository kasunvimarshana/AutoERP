<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceAccountBalance extends TenantOwnedModel
{
    protected $table = 'finance_account_balances';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'account_id' => 'integer',
            'total_debit' => 'decimal:6',
            'total_credit' => 'decimal:6',
            'closing_debit' => 'decimal:6',
            'closing_credit' => 'decimal:6',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }
}
