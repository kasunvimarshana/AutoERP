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
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Hr\DTOs\AttendanceAdjustmentData;
use Modules\Hr\DTOs\AttendanceRegularizationData;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Enums\RegularizationStatus;
use Modules\Hr\Models\HrAttendanceAdjustment;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrAttendanceRegularization;
use Modules\Hr\Models\HrEmployee;

final class AttendanceRegularizationService
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
    ) {}

    public function requestRegularization(int $tenantId, AttendanceRegularizationData $data): HrAttendanceRegularization
    {
        $employee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data->employeeId)
            ->firstOrFail();

        $record = HrAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $data->employeeId)
            ->whereDate('attendance_date', $data->attendanceDate)
            ->first();

        if ($record !== null && $record->lock_status === AttendanceLockStatus::Finalized) {
            throw new InvalidArgumentException('Cannot submit regularization for a finalized attendance period.');
        }

        return HrAttendanceRegularization::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId ?? $employee->organization_unit_id,
            'attendance_record_id' => $data->attendanceRecordId ?? $record?->getKey(),
            'employee_id' => $data->employeeId,
            'attendance_date' => $data->attendanceDate,
            'regularization_type' => $data->regularizationType,
            'proposed_clock_in' => $data->proposedClockIn,
            'proposed_clock_out' => $data->proposedClockOut,
            'reason_code' => $data->reasonCode,
            'employee_notes' => $data->employeeNotes,
            'status' => RegularizationStatus::Pending,
        ]);
    }

    public function actionRegularization(
        HrAttendanceRegularization $regularization,
        int $managerUserId,
        bool $approve,
        ?string $notes = null,
    ): HrAttendanceRegularization {
        if ($regularization->status !== RegularizationStatus::Pending) {
            throw new InvalidArgumentException('Only pending regularizations can be actioned.');
        }

        $tenantId = (int) $regularization->tenant_id;
        $employeeId = (int) $regularization->employee_id;
        $attendanceDate = CarbonImmutable::parse($regularization->attendance_date)->toDateString();

        $record = HrAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        if ($record === null) {
            $record = HrAttendanceRecord::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'attendance_date' => $attendanceDate,
                'organization_unit_id' => $regularization->organization_unit_id,
                'status' => AttendanceStatus::Unresolved,
            ]);
        }

        $prevStatus = $record->status;
        $prevWorked = (int) $record->total_worked_minutes;
        $prevOt = (int) $record->overtime_minutes;

        if ($approve) {
            if ($record->lock_status === AttendanceLockStatus::Finalized) {
                throw new InvalidArgumentException('Cannot approve regularization for a finalized attendance period.');
            }

            // Ingest proposed punches as system-resolved audit punches
            if ($regularization->proposed_clock_in !== null) {
                $this->calculationEngine->recordPunch($tenantId, new PunchData(
                    employeeId: $employeeId,
                    punchType: PunchType::In,
                    punchTime: (string) $regularization->proposed_clock_in,
                    punchSource: PunchSource::SystemResolved,
                    notes: 'Regularization approved by user #'.$managerUserId,
                ));
            }

            if ($regularization->proposed_clock_out !== null) {
                $this->calculationEngine->recordPunch($tenantId, new PunchData(
                    employeeId: $employeeId,
                    punchType: PunchType::Out,
                    punchTime: (string) $regularization->proposed_clock_out,
                    punchSource: PunchSource::SystemResolved,
                    notes: 'Regularization approved by user #'.$managerUserId,
                ));
            }

            $record = $this->calculationEngine->recalculateDailyRecord($tenantId, $employeeId, $attendanceDate);
            $record->update([
                'is_regularized' => true,
            ]);

            // Create immutable audit ledger record
            HrAttendanceAdjustment::create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $regularization->organization_unit_id,
                'attendance_record_id' => $record->getKey(),
                'regularization_id' => $regularization->getKey(),
                'adjusted_by' => $managerUserId,
                'previous_status' => $prevStatus,
                'new_status' => $record->status,
                'previous_worked_minutes' => $prevWorked,
                'new_worked_minutes' => (int) $record->total_worked_minutes,
                'previous_overtime_minutes' => $prevOt,
                'new_overtime_minutes' => (int) $record->overtime_minutes,
                'adjustment_reason' => 'Regularization approved: '.$regularization->reason_code.($notes ? ' - '.$notes : ''),
            ]);
        }

        $regularization->update([
            'row_version' => $regularization->row_version + 1,
            'attendance_record_id' => $record->getKey(),
            'status' => $approve ? RegularizationStatus::Approved : RegularizationStatus::Rejected,
            'actioned_by' => $managerUserId,
            'actioned_at' => now(),
            'action_notes' => $notes,
        ]);

        return $regularization->refresh();
    }

    public function directAdjust(int $tenantId, AttendanceAdjustmentData $data): HrAttendanceAdjustment
    {
        $record = HrAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data->attendanceRecordId)
            ->firstOrFail();

        if ($record->lock_status === AttendanceLockStatus::Finalized) {
            throw new InvalidArgumentException('Cannot adjust a finalized attendance record.');
        }

        $prevStatus = $record->status;
        $prevWorked = (int) $record->total_worked_minutes;
        $prevOt = (int) $record->overtime_minutes;

        // Apply update directly
        $record->update([
            'row_version' => $record->row_version + 1,
            'status' => $data->newStatus,
            'total_worked_minutes' => $data->newWorkedMinutes,
            'overtime_minutes' => $data->newOvertimeMinutes,
            'is_regularized' => true,
        ]);

        return HrAttendanceAdjustment::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $record->organization_unit_id,
            'attendance_record_id' => $record->getKey(),
            'regularization_id' => $data->regularizationId,
            'adjusted_by' => $data->adjustedBy ?? 1,
            'previous_status' => $prevStatus,
            'new_status' => $data->newStatus,
            'previous_worked_minutes' => $prevWorked,
            'new_worked_minutes' => $data->newWorkedMinutes,
            'previous_overtime_minutes' => $prevOt,
            'new_overtime_minutes' => $data->newOvertimeMinutes,
            'adjustment_reason' => $data->adjustmentReason,
        ]);
    }

    public function cancelRegularization(HrAttendanceRegularization $regularization, ?string $notes = null): HrAttendanceRegularization
    {
        if ($regularization->status !== RegularizationStatus::Pending) {
            throw new InvalidArgumentException('Only pending regularizations can be cancelled.');
        }

        $regularization->update([
            'row_version' => ((int) $regularization->row_version) + 1,
            'status' => RegularizationStatus::Cancelled,
            'actioned_at' => now(),
            'action_notes' => $notes,
        ]);

        return $regularization->refresh();
    }

    public function lockRecords(int $tenantId, string $startDate, string $endDate): int
    {
        return HrAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->where('lock_status', AttendanceLockStatus::Open)
            ->update([
                'lock_status' => AttendanceLockStatus::Finalized,
                'row_version' => DB::raw('row_version + 1'),
            ]);
    }
}
