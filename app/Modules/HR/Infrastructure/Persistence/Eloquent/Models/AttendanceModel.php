<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class AttendanceModel extends BaseModel
{
    protected $table = 'hr_attendance_records';
    protected $fillable = [
        'tenant_id', 'employee_id', 'attendance_date',
        'check_in', 'check_out', 'worked_hours',
        'source', 'device_id', 'biometric_data', 'notes', 'is_approved',
    ];
    protected $casts = [
        'id'              => 'int',
        'tenant_id'       => 'int',
        'employee_id'     => 'int',
        'worked_hours'    => 'float',
        'is_approved'     => 'bool',
        'attendance_date' => 'datetime',
        'check_in'        => 'datetime',
        'check_out'       => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];
}
