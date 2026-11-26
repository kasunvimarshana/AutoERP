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
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Finance\Enums\NormalBalance;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceAccount extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'finance_accounts';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::deleting(function (self $account): void {
            if ((bool) $account->is_system) {
                throw new LogicException('System accounts cannot be deleted.');
            }

            if ($account->ledgerEntries()->exists()) {
                throw new LogicException('Posted accounts cannot be deleted.');
            }

            if ($account->journalLines()->exists()) {
                throw new LogicException('Accounts with journal lines cannot be deleted.');
            }

            if ($account->children()->exists()) {
                throw new LogicException('Accounts with child accounts cannot be deleted.');
            }

            if (FinanceAccountAssignment::query()->where('account_id', $account->getKey())->where('is_active', true)->exists()) {
                throw new LogicException('Accounts with active role assignments cannot be deleted.');
            }
        });

        self::updating(function (self $account): void {
            if ($account->isDirty('tenant_id') || $account->isDirty('organization_unit_id')) {
                throw new LogicException('Finance account scope cannot be changed.');
            }

            if ($account->isDirty('normal_balance') && $account->ledgerEntries()->exists()) {
                throw new LogicException('Posted accounts cannot change normal balance.');
            }
        });
    }

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
