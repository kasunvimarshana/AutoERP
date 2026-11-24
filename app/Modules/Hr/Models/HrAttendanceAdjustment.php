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

use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;
use Modules\User\Models\UserModel;

final class HrAttendanceAdjustment extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_attendance_adjustments';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        parent::booted();

        self::updating(function (): void {
            throw new LogicException('Attendance adjustments are immutable historical audit records and cannot be updated.');
        });

        self::deleting(function (): void {
            throw new LogicException('Attendance adjustments are immutable historical audit records and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'attendance_record_id' => 'integer',
            'regularization_id' => 'integer',
            'adjusted_by' => 'integer',
            'previous_status' => AttendanceStatus::class,
            'new_status' => AttendanceStatus::class,
            'previous_worked_minutes' => 'integer',
            'new_worked_minutes' => 'integer',
            'previous_overtime_minutes' => 'integer',
            'new_overtime_minutes' => 'integer',
        ]);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(HrAttendanceRecord::class, 'attendance_record_id');
    }

    public function regularization()
    {
        return $this->belongsTo(HrAttendanceRegularization::class, 'regularization_id');
    }

    public function adjuster()
    {
        return $this->belongsTo(UserModel::class, 'adjusted_by');
    }
}
