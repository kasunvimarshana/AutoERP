<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class ExpenseCategoryModel extends BaseModel
{
    protected $table = 'expense_categories';
    protected $fillable = [
        'tenant_id', 'name', 'code', 'parent_id', 'account_id',
        'is_active', 'description',
    ];
    protected $casts = [
        'id'        => 'int',
        'tenant_id' => 'int',
        'parent_id' => 'int',
        'account_id'=> 'int',
        'is_active' => 'bool',
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
        'deleted_at'=> 'datetime',
    ];
}
