<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class PositionModel extends BaseModel
{
    protected $table = 'hr_positions';
    protected $fillable = [
        'tenant_id', 'department_id', 'title', 'code',
        'description', 'employment_type', 'min_salary', 'max_salary', 'is_active',
    ];
    protected $casts = [
        'id'            => 'int',
        'tenant_id'     => 'int',
        'department_id' => 'int',
        'min_salary'    => 'float',
        'max_salary'    => 'float',
        'is_active'     => 'bool',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];
}
