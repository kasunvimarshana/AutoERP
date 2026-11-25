<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Services;

use Carbon\CarbonImmutable;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrLeaveRequest;

final class PayrollAttendanceIntegrationService
{
    /**
     * @return array{
     *     worked_days: string,
     *     absent_days: string,
     *     unpaid_leave_days: string,
     *     overtime_hours: string,
     *     double_time_hours: string
     * }
     */
    public function aggregateAttendance(int $tenantId, int $employeeId, string $startDate, string $endDate): array
    {
        // Fetch daily attendance records in range
        $records = HrAttendanceRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $workedDays = 0.0;
        $absentDays = 0.0;
        $overtimeMinutes = 0;
        $doubleTimeMinutes = 0;

        foreach ($records as $record) {
            $status = $record->status instanceof AttendanceStatus ? $record->status->value : (string) $record->status;

            if (in_array($status, [AttendanceStatus::Present->value, AttendanceStatus::Late->value], true)) {
                $workedDays += 1.0;
            } elseif ($status === AttendanceStatus::HalfDay->value) {
                $workedDays += 0.5;
                $absentDays += 0.5;
            } elseif ($status === AttendanceStatus::Absent->value) {
                $absentDays += 1.0;
            }

            $overtimeMinutes += (int) $record->overtime_minutes;
            $doubleTimeMinutes += (int) $record->double_time_minutes;
        }

        // Fetch unpaid leave requests approved in range
        $unpaidLeaves = HrLeaveRequest::query()
            ->where('hr_leave_requests.tenant_id', $tenantId)
            ->where('hr_leave_requests.employee_id', $employeeId)
            ->where('hr_leave_requests.status', LeaveRequestStatus::Approved->value)
            ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
            ->where(function ($q) use ($startDate, $endDate): void {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate): void {
                        $sub->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();

        $periodStart = CarbonImmutable::parse($startDate)->startOfDay();
        $periodEnd = CarbonImmutable::parse($endDate)->startOfDay();
        $unpaidDays = 0.0;

        foreach ($unpaidLeaves as $leave) {
            $leaveStart = CarbonImmutable::parse($leave->start_date)->startOfDay();
            $leaveEnd = CarbonImmutable::parse($leave->end_date)->startOfDay();

            if ($leaveStart->greaterThanOrEqualTo($periodStart) && $leaveEnd->lessThanOrEqualTo($periodEnd)) {
                $unpaidDays += (float) $leave->total_days;
            } else {
                $overlapStart = $leaveStart->greaterThan($periodStart) ? $leaveStart : $periodStart;
                $overlapEnd = $leaveEnd->lessThan($periodEnd) ? $leaveEnd : $periodEnd;
                if ($overlapEnd->greaterThanOrEqualTo($overlapStart)) {
                    $unpaidDays += (float) ($overlapStart->diffInDays($overlapEnd) + 1);
                }
            }
        }

        $overtimeHours = bcdiv((string) $overtimeMinutes, '60', 2);
        $doubleTimeHours = bcdiv((string) $doubleTimeMinutes, '60', 2);

        return [
            'worked_days' => sprintf('%.2f', $workedDays),
            'absent_days' => sprintf('%.2f', $absentDays),
            'unpaid_leave_days' => sprintf('%.2f', $unpaidDays),
            'overtime_hours' => $overtimeHours,
            'double_time_hours' => $doubleTimeHours,
        ];
    }
}
