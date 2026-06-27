<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceAccountRole extends TenantOwnedModel
{
    protected $table = 'finance_account_roles';

    protected $guarded = ['id', 'tenant_id', 'row_version'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'is_active' => 'boolean',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(FinanceAccountAssignment::class, 'account_role_id');
    }

    public function postingRules(): HasMany
    {
        return $this->hasMany(FinancePostingProfileRule::class, 'account_role_id');
    }
}
