<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Finance\Enums\NormalBalance;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceAccount extends CoreModel
{
    use SoftDeletes;

    protected $table = 'finance_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'account_type_id' => 'integer',
            'account_category_id' => 'integer',
            'parent_id' => 'integer',
            'normal_balance' => NormalBalance::class,
            'is_control_account' => 'boolean',
            'is_posting_account' => 'boolean',
            'is_cash_account' => 'boolean',
            'is_bank_account' => 'boolean',
            'is_tax_account' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:6',
            'current_balance' => 'decimal:6',
            'metadata' => 'array',
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

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountType::class, 'account_type_id');
    }

    public function accountCategory(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountCategory::class, 'account_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(FinanceJournalLine::class, 'account_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(FinanceLedgerEntry::class, 'account_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(FinanceAccountBalance::class, 'account_id');
    }
}
