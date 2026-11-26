<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\Enums\AnomalyType;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendancePunch;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrLeaveRequest;

final class AttendanceCalculationEngine
{
    public function __construct(
        private readonly ShiftRosterService $rosterService,
    ) {}

    public function recordPunch(int $tenantId, PunchData $data): HrAttendancePunch
    {
        $employee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data->employeeId)
            ->firstOrFail();

        $punchTimestamp = $data->punchTime !== null
            ? CarbonImmutable::parse($data->punchTime)
            : CarbonImmutable::now();

        // Dual-timestamp: Determine operational civil shift date
        $civilDate = $punchTimestamp->toDateString();

        // Check if yesterday had an overnight shift that is still ongoing
        $yesterday = $punchTimestamp->subDay()->toDateString();
        $yesterdayShift = $this->rosterService->resolveShift($tenantId, $data->employeeId, $yesterday);

        if ($yesterdayShift->isOvernight && $punchTimestamp->hour < 12) {
            // Punch belongs to yesterday's overnight shift
            $civilDate = $yesterday;
        }

        if ($data->idempotencyHash !== null) {
            $hashDuplicate = HrAttendancePunch::query()
                ->where('tenant_id', $tenantId)
                ->where('idempotency_hash', $data->idempotencyHash)
                ->first();
            if ($hashDuplicate !== null) {
                return $hashDuplicate;
            }
        }

        // Multi-punch deduplication within 120 seconds for same type
        $recentDuplicate = HrAttendancePunch::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $data->employeeId)
            ->where('punch_type', $data->punchType)
            ->where('punch_time', '>=', $punchTimestamp->subSeconds(120))
            ->where('punch_time', '<=', $punchTimestamp->addSeconds(120))
            ->exists();

        $punch = HrAttendancePunch::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId ?? $employee->organization_unit_id,
            'employee_id' => $data->employeeId,
            'attendance_date' => $civilDate,
            'punch_time' => $punchTimestamp,
            'punch_type' => $data->punchType,
            'punch_source' => $data->punchSource,
            'capture_method' => $data->captureMethod,
            'credential_id' => $data->credentialId,
            'accuracy_meters' => $data->accuracyMeters,
            'device_fingerprint' => $data->deviceFingerprint,
            'photo_path' => $data->photoPath,
            'geofence_id' => $data->geofenceId,
            'idempotency_hash' => $data->idempotencyHash,
            'device_id' => $data->deviceId,
            'ip_address' => $data->ipAddress,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'is_verified' => true,
            'is_ignored' => $recentDuplicate,
            'notes' => $data->notes,
        ]);

        // Recalculate daily attendance record if punch is valid
        if (! $recentDuplicate) {
            $record = $this->recalculateDailyRecord($tenantId, $data->employeeId, $civilDate);
            $punch->update(['attendance_record_id' => $record->getKey()]);
        }

        return $punch->refresh();
    }

    public function recalculateDailyRecord(int $tenantId, int $employeeId, string $attendanceDate): HrAttendanceRecord
    {
        $employee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId)
            ->firstOrFail();

        $dateStr = CarbonImmutable::parse($attendanceDate)->toDateString();

        $record = HrAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $dateStr)
            ->first();

        if ($record === null) {
            $record = HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'attendance_date' => $dateStr,
                'organization_unit_id' => $employee->organization_unit_id,
                'status' => AttendanceStatus::Unresolved,
                'lock_status' => AttendanceLockStatus::Open,
            ]);
        }

        if ($record->lock_status === AttendanceLockStatus::Finalized) {
            throw new InvalidArgumentException('Cannot recalculate finalized attendance records.');
        }

        $resolvedShift = $this->rosterService->resolveShift($tenantId, $employeeId, $attendanceDate);
        $shift = $resolvedShift->shift;

        // Check if employee has an approved leave covering this date
        $hasApprovedLeave = HrLeaveRequest::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $attendanceDate)
            ->whereDate('end_date', '>=', $attendanceDate)
            ->where('status', LeaveRequestStatus::Approved)
            ->exists();

        // Get non-ignored punches sorted by punch_time
        $punches = HrAttendancePunch::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $attendanceDate)
            ->where('is_ignored', false)
            ->orderBy('punch_time', 'asc')
            ->get();

        // Link punches to this record
        foreach ($punches as $punch) {
            if ($punch->attendance_record_id !== $record->getKey()) {
                $punch->update(['attendance_record_id' => $record->getKey()]);
            }
        }

        $shiftBreaks = $shift !== null
            ? ($shift->relationLoaded('breaks') ? $shift->breaks : $shift->breaks()->get())
            : collect();

        $firstIn = null;
        $lastOut = null;
        $breakMinutes = 0;
        $unpaidBreakMinutes = 0;
        $activeBreakStart = null;

        foreach ($punches as $p) {
            $time = CarbonImmutable::parse($p->punch_time);

            if ($p->punch_type === PunchType::In && $firstIn === null) {
                $firstIn = $time;
            } elseif ($p->punch_type === PunchType::Out) {
                $lastOut = $time;
            } elseif (in_array($p->punch_type, [PunchType::BreakOut, PunchType::MealOut], true)) {
                $activeBreakStart = $time;
            } elseif (in_array($p->punch_type, [PunchType::BreakIn, PunchType::MealIn], true) && $activeBreakStart !== null) {
                $duration = (int) $activeBreakStart->diffInMinutes($time);
                $breakMinutes += $duration;

                $matchedBreak = $p->punch_type === PunchType::MealIn
                    ? $shiftBreaks->firstWhere('break_type', 'meal')
                    : $shiftBreaks->first(static fn ($b): bool => $b->break_type !== 'meal');
                $isPaid = $matchedBreak !== null
                    ? (bool) $matchedBreak->is_paid
                    : ($shift?->is_break_paid ?? false);

                if (! $isPaid) {
                    $unpaidBreakMinutes += $duration;
                }
                $activeBreakStart = null;
            }
        }

        $scheduledMinutes = $resolvedShift->scheduledMinutes;
        $totalWorkedMinutes = 0;
        $lateArrivalMinutes = 0;
        $earlyDepartureMinutes = 0;
        $overtimeMinutes = 0;
        $doubleTimeMinutes = 0;

        if ($firstIn !== null && $lastOut !== null && $lastOut->greaterThan($firstIn)) {
            $elapsedMinutes = (int) $firstIn->diffInMinutes($lastOut);
            $totalWorkedMinutes = max(0, $elapsedMinutes - $unpaidBreakMinutes);

            // Auto deduct mandatory configured shift breaks or legacy auto_deduct_break when no break was punched
            if ($breakMinutes === 0 && $shift !== null) {
                $mandatoryBreaks = $shiftBreaks->where('is_mandatory', true);
                if ($mandatoryBreaks->isNotEmpty()) {
                    $mandatoryTotal = (int) $mandatoryBreaks->sum('duration_minutes');
                    $mandatoryUnpaid = (int) $mandatoryBreaks->where('is_paid', false)->sum('duration_minutes');
                    if ($totalWorkedMinutes >= (int) $shift->auto_deduct_threshold_minutes && $totalWorkedMinutes > $mandatoryTotal) {
                        $totalWorkedMinutes = max(0, $totalWorkedMinutes - $mandatoryUnpaid);
                        $unpaidBreakMinutes += $mandatoryUnpaid;
                        $breakMinutes += $mandatoryTotal;
                    }
                } elseif ($shift->auto_deduct_break && $totalWorkedMinutes >= (int) $shift->auto_deduct_threshold_minutes) {
                    $deduction = (int) $shift->auto_deduct_break_minutes;
                    $totalWorkedMinutes = max(0, $totalWorkedMinutes - $deduction);
                    $unpaidBreakMinutes += $deduction;
                    $breakMinutes += $deduction;
                }
            }

            // Tardiness calculation
            if ($resolvedShift->isWorkingDay && $resolvedShift->startTime !== null) {
                $scheduledStart = CarbonImmutable::parse("{$attendanceDate} {$resolvedShift->startTime}");
                $graceStart = $scheduledStart->addMinutes($resolvedShift->graceInMinutes);
                if ($firstIn->greaterThan($graceStart)) {
                    $lateArrivalMinutes = (int) $scheduledStart->diffInMinutes($firstIn);
                }
            }

            // Early departure calculation
            if ($resolvedShift->isWorkingDay && $resolvedShift->endTime !== null) {
                $scheduledEnd = CarbonImmutable::parse("{$attendanceDate} {$resolvedShift->endTime}");
                if ($resolvedShift->isOvernight) {
                    $scheduledEnd = $scheduledEnd->addDay();
                }
                $graceEnd = $scheduledEnd->subMinutes($resolvedShift->graceOutMinutes);
                if ($lastOut->lessThan($graceEnd)) {
                    $earlyDepartureMinutes = (int) $lastOut->diffInMinutes($scheduledEnd);
                }
            }

            // Overtime & Double time
            if ($resolvedShift->isHoliday || $resolvedShift->isRestDay) {
                $doubleTimeMinutes = $totalWorkedMinutes;
                $regularMinutes = 0;
            } else {
                $regularMinutes = min($totalWorkedMinutes, $scheduledMinutes);
                if ($totalWorkedMinutes > $scheduledMinutes && $scheduledMinutes > 0) {
                    $overtimeMinutes = $totalWorkedMinutes - $scheduledMinutes;
                }
            }
        } else {
            $regularMinutes = 0;
        }

        $anomalyType = null;
        $requiresRegularization = false;

        // Attendance Status Determination
        if ($hasApprovedLeave) {
            $status = AttendanceStatus::OnLeave;
        } elseif ($punches->isEmpty()) {
            if ($resolvedShift->isHoliday) {
                $status = AttendanceStatus::Holiday;
            } elseif ($resolvedShift->isRestDay) {
                $status = AttendanceStatus::RestDay;
            } elseif (CarbonImmutable::parse($attendanceDate)->isPast() && ! CarbonImmutable::parse($attendanceDate)->isToday()) {
                $status = AttendanceStatus::Absent;
            } else {
                $status = AttendanceStatus::Unresolved;
            }
        } else {
            // Punches exist
            if ($firstIn === null || $lastOut === null) {
                $status = AttendanceStatus::Unresolved;
                $anomalyType = $firstIn === null ? AnomalyType::MissingIn : AnomalyType::MissingOut;
                $requiresRegularization = true;
            } else {
                if ($activeBreakStart !== null) {
                    $anomalyType = AnomalyType::UnpairedBreak;
                    $requiresRegularization = true;
                } elseif ($totalWorkedMinutes > 16 * 60) {
                    $anomalyType = AnomalyType::ExcessiveHours;
                    $requiresRegularization = true;
                }

                $halfDayThreshold = $shift?->half_day_threshold_minutes ?? 240;
                $fullDayThreshold = $shift?->full_day_threshold_minutes ?? 480;

                if ($totalWorkedMinutes < $halfDayThreshold) {
                    $status = AttendanceStatus::HalfDay;
                } elseif ($lateArrivalMinutes > 0) {
                    $status = AttendanceStatus::Late;
                } elseif ($totalWorkedMinutes >= $fullDayThreshold || $totalWorkedMinutes >= $scheduledMinutes) {
                    $status = AttendanceStatus::Present;
                } else {
                    $status = AttendanceStatus::HalfDay;
                }
            }
        }

        $record->update([
            'row_version' => $record->row_version + 1,
            'shift_id' => $shift?->getKey(),
            'first_punch_in' => $firstIn?->toDateTimeString(),
            'last_punch_out' => $lastOut?->toDateTimeString(),
            'scheduled_minutes' => $scheduledMinutes,
            'total_worked_minutes' => $totalWorkedMinutes,
            'total_break_minutes' => $breakMinutes,
            'unpaid_break_minutes' => $unpaidBreakMinutes,
            'regular_minutes' => $regularMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'double_time_minutes' => $doubleTimeMinutes,
            'late_arrival_minutes' => $lateArrivalMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'status' => $status,
            'anomaly_type' => $anomalyType,
            'requires_regularization' => $requiresRegularization,
        ]);

        return $record->refresh();
    }
}
