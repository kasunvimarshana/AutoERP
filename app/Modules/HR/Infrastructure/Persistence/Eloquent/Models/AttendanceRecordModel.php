<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AttendanceRecordModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_attendance_records';

    protected $fillable = ['tenant_id', 'org_unit_id', 'row_version', 'employee_id', 'attendance_date', 'check_in', 'check_out', 'break_duration', 'worked_minutes', 'overtime_minutes', 'status', 'shift_id', 'remarks', 'metadata'];

    protected $casts = ['tenant_id' => 'integer', 'org_unit_id' => 'integer', 'row_version' => 'integer', 'employee_id' => 'integer', 'shift_id' => 'integer', 'status' => 'string', 'attendance_date' => 'date', 'check_in' => 'datetime', 'check_out' => 'datetime', 'metadata' => 'array', 'break_duration' => 'integer', 'worked_minutes' => 'integer', 'overtime_minutes' => 'integer'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(ShiftModel::class, 'shift_id');
    }
}
