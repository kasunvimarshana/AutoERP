<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\CoreModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceAccountCategory extends CoreModel
{
    protected $table = 'finance_account_categories';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'account_type_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountType::class, 'account_type_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(FinanceAccount::class, 'account_category_id');
    }
}
