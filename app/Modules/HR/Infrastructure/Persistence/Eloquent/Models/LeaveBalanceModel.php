<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class LeaveBalanceModel extends BaseModel
{
    use HasTenant;

    protected $table = 'hr_leave_balances';

    protected $fillable = ['tenant_id', 'org_unit_id', 'row_version', 'employee_id', 'leave_type_id', 'year', 'allocated', 'used', 'pending', 'carried'];

    protected $casts = ['tenant_id' => 'integer', 'org_unit_id' => 'integer', 'row_version' => 'integer', 'employee_id' => 'integer', 'leave_type_id' => 'integer', 'year' => 'integer', 'allocated' => 'decimal:2', 'used' => 'decimal:2', 'pending' => 'decimal:2', 'carried' => 'decimal:2'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeModel::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveTypeModel::class, 'leave_type_id');
    }
}
