<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class DepartmentModel extends BaseModel
{
    protected $table = 'hr_departments';
    protected $fillable = [
        'tenant_id', 'name', 'code', 'description',
        'manager_id', 'parent_id', 'is_active',
    ];
    protected $casts = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'manager_id' => 'int',
        'parent_id'  => 'int',
        'is_active'  => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
