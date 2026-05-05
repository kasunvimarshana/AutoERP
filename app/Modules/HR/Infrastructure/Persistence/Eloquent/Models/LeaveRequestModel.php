<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class LeaveRequestModel extends BaseModel
{
    use HasAudit, HasTenant;

    protected $table = 'hr_leave_requests';

    protected $fillable = ['tenant_id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days', 'reason', 'status', 'approver_id', 'approver_note', 'attachment_path', 'metadata'];

    protected $casts = ['tenant_id' => 'integer', 'employee_id' => 'integer', 'leave_type_id' => 'integer', 'approver_id' => 'integer', 'start_date' => 'date', 'end_date' => 'date', 'total_days' => 'float', 'metadata' => 'array'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveTypeModel::class, 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approver_id');
    }
}
