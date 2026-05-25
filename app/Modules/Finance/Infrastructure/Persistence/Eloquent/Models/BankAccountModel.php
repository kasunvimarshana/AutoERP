<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasActiveScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasReferenceScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CheckModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class BankAccountModel extends Model
{
    use HasActiveScope, HasOrganizationUnitScope, HasReferenceScope, HasTenantScope, SoftDeletes;

    protected $table = 'bank_accounts';

    protected $guarded = ['id'];

    protected static string $referenceColumn = 'name';

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'created_by' => 'integer',
            'currency_id' => 'integer',
            'current_balance' => 'decimal:4',
            'is_active' => 'boolean',
            'last_reconciled_at' => 'datetime',
            'last_reconciled_balance' => 'decimal:4',
            'metadata' => 'array',
            'opening_balance' => 'decimal:4',
            'organization_unit_id' => 'integer',
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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function bankCategoryRules(): HasMany
    {
        return $this->hasMany(BankCategoryRuleModel::class, 'bank_account_id');
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransactionModel::class, 'bank_account_id');
    }

    public function bankReconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliationModel::class, 'bank_account_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(CheckModel::class, 'bank_account_id');
    }
}

