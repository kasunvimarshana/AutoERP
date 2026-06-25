<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\Finance\Enums\NormalBalance;
use Modules\Finance\Enums\StatementType;
use Modules\Tenant\Models\TenantModel;

final class FinanceAccountType extends CoreModel
{
    protected $table = 'finance_account_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'normal_balance' => NormalBalance::class,
            'statement_type' => StatementType::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(FinanceAccountCategory::class, 'account_type_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(FinanceAccount::class, 'account_type_id');
    }
}
