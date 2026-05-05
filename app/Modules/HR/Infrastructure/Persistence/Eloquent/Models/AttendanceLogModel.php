<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AttendanceLogModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_attendance_logs';

    protected $fillable = ['tenant_id', 'org_unit_id', 'row_version', 'employee_id', 'biometric_device_id', 'punch_time', 'punch_type', 'source', 'raw_data', 'processed_at'];

    protected $casts = ['tenant_id' => 'integer', 'org_unit_id' => 'integer', 'row_version' => 'integer', 'employee_id' => 'integer', 'biometric_device_id' => 'integer', 'punch_type' => 'string', 'source' => 'string', 'raw_data' => 'array', 'punch_time' => 'datetime', 'processed_at' => 'datetime'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDeviceModel::class, 'biometric_device_id');
    }
}
