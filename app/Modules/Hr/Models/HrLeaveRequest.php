<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\LeaveDurationType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;
use Modules\User\Models\UserModel;

final class HrLeaveRequest extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_leave_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'leave_type_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_type' => LeaveDurationType::class,
            'total_days' => 'decimal:2',
            'attachment_document_id' => 'integer',
            'status' => LeaveRequestStatus::class,
            'approver_id' => 'integer',
            'approved_at' => 'datetime',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approver_id');
    }

    public function attachmentDocument(): BelongsTo
    {
        return $this->belongsTo(HrEmployeeDocument::class, 'attachment_document_id');
    }
}
