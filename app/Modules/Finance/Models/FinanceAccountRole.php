<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;

final class FinanceAccountRole extends TenantOwnedModel
{
    protected $table = 'finance_account_roles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'is_active' => 'boolean',
        ]);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(FinanceAccountAssignment::class, 'account_role_id');
    }

    public function postingProfileRules(): HasMany
    {
        return $this->hasMany(FinancePostingProfileRule::class, 'account_role_id');
    }
}
