<?php

declare(strict_types=1);

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;

final class ExpenseType extends TenantOwnedModel
{
    use SoftDeletes;

    protected $table = 'expense_types';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
        ]);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_type_id');
    }
}
