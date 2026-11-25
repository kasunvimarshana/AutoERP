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
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Hr\Enums\AnomalyType;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Models\Concerns\ScopesHrTenant;

final class HrAttendanceRecord extends TenantOwnedModel
{
    use ScopesHrTenant;

    protected $table = 'hr_attendance_records';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        parent::booted();

        self::updating(function (self $record): void {
            $originalLock = $record->getOriginal('lock_status');
            $isFinalized = ($originalLock instanceof AttendanceLockStatus && $originalLock === AttendanceLockStatus::Finalized)
                || $originalLock === AttendanceLockStatus::Finalized->value
                || $record->getRawOriginal('lock_status') === AttendanceLockStatus::Finalized->value;

            if ($isFinalized && ! $record->isDirty('lock_status')) {
                throw new LogicException('Finalized attendance records cannot be updated directly. Use attendance regularization or adjustments.');
            }
        });

        self::deleting(function (self $record): void {
            $lockStatus = $record->lock_status;
            $isFinalized = ($lockStatus instanceof AttendanceLockStatus && $lockStatus === AttendanceLockStatus::Finalized)
                || $lockStatus === AttendanceLockStatus::Finalized->value;

            if ($isFinalized) {
                throw new LogicException('Finalized attendance records cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'employee_id' => 'integer',
            'attendance_date' => 'date',
            'shift_id' => 'integer',
            'first_punch_in' => 'datetime',
            'last_punch_out' => 'datetime',
            'scheduled_minutes' => 'integer',
            'total_worked_minutes' => 'integer',
            'total_break_minutes' => 'integer',
            'unpaid_break_minutes' => 'integer',
            'regular_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'double_time_minutes' => 'integer',
            'late_arrival_minutes' => 'integer',
            'early_departure_minutes' => 'integer',
            'status' => AttendanceStatus::class,
            'lock_status' => AttendanceLockStatus::class,
            'is_regularized' => 'boolean',
            'anomaly_type' => AnomalyType::class,
            'requires_regularization' => 'boolean',
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

    public function punches(): HasMany
    {
        return $this->hasMany(HrAttendancePunch::class, 'attendance_record_id');
    }

    public function regularizations(): HasMany
    {
        return $this->hasMany(HrAttendanceRegularization::class, 'attendance_record_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(HrAttendanceAdjustment::class, 'attendance_record_id');
    }
}
