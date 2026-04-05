<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class LeaveTypeModel extends BaseModel
{
    protected $table = 'hr_leave_types';
    protected $fillable = [
        'tenant_id', 'name', 'code', 'description',
        'default_days', 'is_paid', 'requires_approval', 'is_active',
    ];
    protected $casts = [
        'id'                => 'int',
        'tenant_id'         => 'int',
        'default_days'      => 'int',
        'is_paid'           => 'bool',
        'requires_approval' => 'bool',
        'is_active'         => 'bool',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];
}
