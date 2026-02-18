<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Expense Category Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $account_id
 */
class ExpenseCategory extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_expense_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'account_id',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
