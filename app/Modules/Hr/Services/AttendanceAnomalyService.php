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
use Modules\Hr\DTOs\AttendanceRegularizationData;
use Modules\Hr\Enums\AnomalyType;
use Modules\Hr\Enums\AttendanceLockStatus;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\RegularizationType;
use Modules\Hr\Models\HrAttendanceRecord;

final class AttendanceAnomalyService
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
        private readonly AttendanceRegularizationService $regularizationService,
    ) {}

    /**
     * Scan attendance records for a date and detect anomalies (missing in/out, unpaired breaks, excessive hours).
     *
     * @return array{total_scanned: int, anomalies_detected: int, records: list<HrAttendanceRecord>}
     */
    public function scanAnomalies(int $tenantId, ?string $date = null, ?int $employeeId = null): array
    {
        $targetDate = $date ?? CarbonImmutable::yesterday()->toDateString();

        $query = HrAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('attendance_date', $targetDate)
            ->where('lock_status', '!=', AttendanceLockStatus::Finalized);

        if ($employeeId !== null) {
            $query->where('employee_id', $employeeId);
        }

        $records = $query->get();
        $anomalies = [];

        foreach ($records as $record) {
            $empId = (int) $record->employee_id;
            $recalculated = $this->calculationEngine->recalculateDailyRecord($tenantId, $empId, $targetDate);

            $hasAnomaly = false;
            $detectedType = null;

            if ($recalculated->first_punch_in !== null && $recalculated->last_punch_out === null) {
                $hasAnomaly = true;
                $detectedType = AnomalyType::MissingOut;
            } elseif ($recalculated->first_punch_in === null && $recalculated->last_punch_out !== null) {
                $hasAnomaly = true;
                $detectedType = AnomalyType::MissingIn;
            } elseif ((int) $recalculated->total_worked_minutes > 16 * 60) {
                $hasAnomaly = true;
                $detectedType = AnomalyType::ExcessiveHours;
            } elseif ((int) $recalculated->scheduled_minutes > 240 && (int) $recalculated->total_worked_minutes > 0 && (int) $recalculated->total_worked_minutes < 60) {
                $hasAnomaly = true;
                $detectedType = AnomalyType::ShortShift;
            }

            if ($hasAnomaly && $detectedType !== null) {
                $recalculated->update([
                    'status' => in_array($detectedType, [AnomalyType::MissingIn, AnomalyType::MissingOut], true)
                        ? AttendanceStatus::MissingPunch
                        : AttendanceStatus::Anomalous,
                    'anomaly_type' => $detectedType,
                    'requires_regularization' => true,
                ]);

                $anomalies[] = $recalculated->refresh();
            }
        }

        return [
            'total_scanned' => $records->count(),
            'anomalies_detected' => count($anomalies),
            'records' => $anomalies,
        ];
    }

    /**
     * Admin quick-resolve an anomalous or missing punch attendance record.
     */
    public function quickResolveAnomaly(
        HrAttendanceRecord $record,
        int $resolvedByUserId,
        ?string $inTime,
        ?string $outTime,
        string $resolutionReason,
    ): HrAttendanceRecord {
        if ($record->lock_status === AttendanceLockStatus::Finalized) {
            throw new InvalidArgumentException('Cannot resolve a finalized attendance record.');
        }

        $tenantId = (int) $record->tenant_id;
        $employeeId = (int) $record->employee_id;
        $attendanceDate = CarbonImmutable::parse($record->attendance_date)->toDateString();

        $regType = RegularizationType::ForgotToPunch;
        if ($record->anomaly_type === AnomalyType::MissingOut) {
            $regType = RegularizationType::MissingOut;
        } elseif ($record->anomaly_type === AnomalyType::MissingIn) {
            $regType = RegularizationType::MissingIn;
        }

        // 1. Submit regularization
        $regularization = $this->regularizationService->requestRegularization(
            $tenantId,
            new AttendanceRegularizationData(
                employeeId: $employeeId,
                attendanceDate: $attendanceDate,
                regularizationType: $regType,
                reasonCode: 'admin_resolution',
                employeeNotes: $resolutionReason,
                proposedClockIn: $inTime,
                proposedClockOut: $outTime,
                attendanceRecordId: (int) $record->id,
            ),
        );

        // 2. Action and approve directly
        $this->regularizationService->actionRegularization(
            $regularization,
            $resolvedByUserId,
            true,
            "Quick-resolved by admin: {$resolutionReason}",
        );

        // 3. Mark record anomaly as resolved
        $record->refresh();
        $record->update([
            'anomaly_type' => null,
            'requires_regularization' => false,
            'is_regularized' => true,
        ]);

        return $record->refresh();
    }
}
