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
use Modules\Hr\Enums\RegularizationStatus;
use Modules\Hr\Enums\RegularizationType;
use Modules\Hr\Models\Concerns\ScopesHrTenant;
use Modules\User\Models\UserModel;

final class HrAttendanceRegularization extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_attendance_regularizations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'attendance_record_id' => 'integer',
            'employee_id' => 'integer',
            'attendance_date' => 'date',
            'regularization_type' => RegularizationType::class,
            'proposed_clock_in' => 'datetime',
            'proposed_clock_out' => 'datetime',
            'status' => RegularizationStatus::class,
            'actioned_by' => 'integer',
            'actioned_at' => 'datetime',
        ]);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(HrAttendanceRecord::class, 'attendance_record_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function actioner(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actioned_by');
    }
}
