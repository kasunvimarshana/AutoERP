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
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Models\Concerns\ScopesHrTenant;
use Modules\User\Models\UserModel;

final class HrShiftAssignment extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_shift_assignments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'shift_id' => 'integer',
            'assignment_type' => ShiftAssignmentType::class,
            'day_of_week' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_custom_timing' => 'boolean',
            'is_cancelled' => 'boolean',
            'assigned_by' => 'integer',
            'assigned_at' => 'datetime',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assigned_by');
    }
}
