<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class LeaveRequestModel extends BaseModel
{
    protected $table = 'hr_leave_requests';
    protected $fillable = [
        'tenant_id', 'employee_id', 'leave_type_id',
        'start_date', 'end_date', 'total_days', 'status',
        'reason', 'approved_by_id', 'approved_at', 'rejection_reason',
    ];
    protected $casts = [
        'id'              => 'int',
        'tenant_id'       => 'int',
        'employee_id'     => 'int',
        'leave_type_id'   => 'int',
        'approved_by_id'  => 'int',
        'total_days'      => 'float',
        'start_date'      => 'datetime',
        'end_date'        => 'datetime',
        'approved_at'     => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];
}
