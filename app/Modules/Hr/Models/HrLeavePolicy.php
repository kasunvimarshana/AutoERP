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
use Modules\Hr\Enums\LeaveAccrualFrequency;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrLeavePolicy extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_leave_policies';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'leave_type_id' => 'integer',
            'department_id' => 'integer',
            'employment_type_id' => 'integer',
            'annual_allocation_days' => 'decimal:2',
            'accrual_frequency' => LeaveAccrualFrequency::class,
            'max_carry_forward_days' => 'decimal:2',
            'carry_forward_expiry_months' => 'integer',
            'max_consecutive_days' => 'integer',
            'probation_restricted' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ]);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(HrEmploymentType::class, 'employment_type_id');
    }
}
