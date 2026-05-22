<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class BankAccount extends Model
{
    use HasTenantAndOrganizationScopes;
    use SoftDeletes;

    protected $table = 'bank_accounts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'opening_balance' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'last_reconciled_at' => 'datetime',
            'last_reconciled_balance' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo('Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency', 'currency_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function categoryRules(): HasMany
    {
        return $this->hasMany(BankCategoryRule::class, 'bank_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'bank_account_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class, 'bank_account_id');
    }
}
