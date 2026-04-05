<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class BudgetModel extends BaseModel
{
    protected $table = 'budgets';
    protected $fillable = [
        'tenant_id', 'account_id', 'expense_category_id', 'name',
        'period_start', 'period_end', 'amount', 'spent_amount',
        'currency', 'notes',
    ];
    protected $casts = [
        'id'                  => 'int',
        'tenant_id'           => 'int',
        'account_id'          => 'int',
        'expense_category_id' => 'int',
        'amount'              => 'float',
        'spent_amount'        => 'float',
        'period_start'        => 'date',
        'period_end'          => 'date',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];
}
