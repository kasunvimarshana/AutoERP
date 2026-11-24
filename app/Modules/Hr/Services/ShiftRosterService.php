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
use Modules\Hr\DTOs\ResolvedShiftData;
use Modules\Hr\DTOs\ShiftAssignmentData;
use Modules\Hr\DTOs\ShiftData;
use Modules\Hr\DTOs\ShiftReallocationData;
use Modules\Hr\DTOs\ShiftSwapData;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Enums\ShiftSwapStatus;
use Modules\Hr\Models\HrHoliday;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Models\HrShiftReallocation;
use Modules\Hr\Models\HrShiftSwap;

final class ShiftRosterService
{
    public function createShift(int $tenantId, ShiftData $data): HrShift
    {
        return HrShift::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'code' => $data->code,
            'name' => $data->name,
            'color' => $data->color,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'is_overnight' => $data->isOvernight,
            'grace_in_minutes' => $data->graceInMinutes,
            'grace_out_minutes' => $data->graceOutMinutes,
            'half_day_threshold_minutes' => $data->halfDayThresholdMinutes,
            'full_day_threshold_minutes' => $data->fullDayThresholdMinutes,
            'is_break_paid' => $data->isBreakPaid,
            'auto_deduct_break' => $data->autoDeductBreak,
            'auto_deduct_break_minutes' => $data->autoDeductBreakMinutes,
            'auto_deduct_threshold_minutes' => $data->autoDeductThresholdMinutes,
            'is_flexible' => $data->isFlexible,
            'status' => $data->status,
            'notes' => $data->notes,
        ]);
    }

    public function updateShift(HrShift $shift, ShiftData $data): HrShift
    {
        $shift->update([
            'row_version' => $shift->row_version + 1,
            'organization_unit_id' => $data->organizationUnitId ?? $shift->organization_unit_id,
            'code' => $data->code,
            'name' => $data->name,
            'color' => $data->color,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'is_overnight' => $data->isOvernight,
            'grace_in_minutes' => $data->graceInMinutes,
            'grace_out_minutes' => $data->graceOutMinutes,
            'half_day_threshold_minutes' => $data->halfDayThresholdMinutes,
            'full_day_threshold_minutes' => $data->fullDayThresholdMinutes,
            'is_break_paid' => $data->isBreakPaid,
            'auto_deduct_break' => $data->autoDeductBreak,
            'auto_deduct_break_minutes' => $data->autoDeductBreakMinutes,
            'auto_deduct_threshold_minutes' => $data->autoDeductThresholdMinutes,
            'is_flexible' => $data->isFlexible,
            'status' => $data->status,
            'notes' => $data->notes,
        ]);

        return $shift->refresh();
    }

    public function assignShift(int $tenantId, ShiftAssignmentData $data): HrShiftAssignment
    {
        return HrShiftAssignment::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'employee_id' => $data->employeeId,
            'shift_id' => $data->shiftId,
            'assignment_type' => $data->assignmentType,
            'day_of_week' => $data->dayOfWeek,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'is_custom_timing' => $data->isCustomTiming,
            'custom_start_time' => $data->customStartTime,
            'custom_end_time' => $data->customEndTime,
            'assigned_by' => $data->assignedBy,
            'assigned_at' => now(),
        ]);
    }

    public function cancelAssignment(HrShiftAssignment $assignment, string $reason): HrShiftAssignment
    {
        $assignment->update([
            'row_version' => $assignment->row_version + 1,
            'is_cancelled' => true,
            'cancellation_reason' => $reason,
        ]);

        return $assignment->refresh();
    }

    public function requestSwap(int $tenantId, ShiftSwapData $data): HrShiftSwap
    {
        if ($data->requesterEmployeeId === $data->targetEmployeeId) {
            throw new InvalidArgumentException('Cannot request a shift swap with yourself.');
        }

        $requesterShift = $this->resolveShift($tenantId, $data->requesterEmployeeId, $data->swapDate);
        $targetShift = $this->resolveShift($tenantId, $data->targetEmployeeId, $data->targetSwapDate);

        return HrShiftSwap::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'requester_employee_id' => $data->requesterEmployeeId,
            'target_employee_id' => $data->targetEmployeeId,
            'requester_shift_id' => $data->requesterShiftId ?? $requesterShift->shift?->getKey(),
            'target_shift_id' => $data->targetShiftId ?? $targetShift->shift?->getKey(),
            'swap_date' => $data->swapDate,
            'target_swap_date' => $data->targetSwapDate,
            'reason' => $data->reason,
            'status' => ShiftSwapStatus::PendingPeer,
        ]);
    }

    public function peerActionSwap(HrShiftSwap $swap, int $peerEmployeeId, bool $accept): HrShiftSwap
    {
        if ($swap->target_employee_id !== $peerEmployeeId) {
            throw new InvalidArgumentException('Only the designated peer can act on this shift swap.');
        }

        if ($swap->status !== ShiftSwapStatus::PendingPeer) {
            throw new InvalidArgumentException('Shift swap is not in pending peer status.');
        }

        $swap->update([
            'row_version' => $swap->row_version + 1,
            'status' => $accept ? ShiftSwapStatus::PeerAccepted : ShiftSwapStatus::PeerRejected,
            'peer_actioned_at' => now(),
        ]);

        return $swap->refresh();
    }

    public function managerActionSwap(HrShiftSwap $swap, int $managerUserId, bool $approve, ?string $notes = null): HrShiftSwap
    {
        if ($swap->status !== ShiftSwapStatus::PeerAccepted) {
            throw new InvalidArgumentException('Shift swap must be accepted by the peer before manager approval.');
        }

        $swap->update([
            'row_version' => $swap->row_version + 1,
            'status' => $approve ? ShiftSwapStatus::ManagerApproved : ShiftSwapStatus::ManagerRejected,
            'manager_actioned_by' => $managerUserId,
            'manager_actioned_at' => now(),
            'manager_notes' => $notes,
        ]);

        return $swap->refresh();
    }

    public function reallocateCover(int $tenantId, ShiftReallocationData $data): HrShiftReallocation
    {
        if ($data->absentEmployeeId === $data->coverEmployeeId) {
            throw new InvalidArgumentException('Absent employee and cover employee cannot be the same.');
        }

        return HrShiftReallocation::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'absent_employee_id' => $data->absentEmployeeId,
            'cover_employee_id' => $data->coverEmployeeId,
            'shift_id' => $data->shiftId,
            'reallocation_date' => $data->reallocationDate,
            'reason_code' => $data->reasonCode,
            'notes' => $data->notes,
            'reallocated_by' => $data->reallocatedBy,
        ]);
    }

    public function resolveShift(int $tenantId, int $employeeId, string $civilDate): ResolvedShiftData
    {
        $date = CarbonImmutable::parse($civilDate);

        // 1. Check Shift Reallocation (Cover assignment)
        $reallocation = HrShiftReallocation::query()
            ->where('tenant_id', $tenantId)
            ->where('cover_employee_id', $employeeId)
            ->whereDate('reallocation_date', $civilDate)
            ->with('shift')
            ->first();

        if ($reallocation !== null && $reallocation->shift !== null) {
            return $this->buildResolvedShift($reallocation->shift, $civilDate, 'reallocation');
        }

        // 2. Check Approved Shift Swap
        $swapAsRequester = HrShiftSwap::query()
            ->where('tenant_id', $tenantId)
            ->where('requester_employee_id', $employeeId)
            ->whereDate('swap_date', $civilDate)
            ->where('status', ShiftSwapStatus::ManagerApproved)
            ->with('targetShift')
            ->first();

        if ($swapAsRequester !== null && $swapAsRequester->targetShift !== null) {
            return $this->buildResolvedShift($swapAsRequester->targetShift, $civilDate, 'swap');
        }

        $swapAsTarget = HrShiftSwap::query()
            ->where('tenant_id', $tenantId)
            ->where('target_employee_id', $employeeId)
            ->whereDate('target_swap_date', $civilDate)
            ->where('status', ShiftSwapStatus::ManagerApproved)
            ->with('requesterShift')
            ->first();

        if ($swapAsTarget !== null && $swapAsTarget->requesterShift !== null) {
            return $this->buildResolvedShift($swapAsTarget->requesterShift, $civilDate, 'swap');
        }

        // 3. Check Specific-Date Override
        $specificDate = HrShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('assignment_type', ShiftAssignmentType::SpecificDate)
            ->whereDate('start_date', $civilDate)
            ->where('is_cancelled', false)
            ->with('shift')
            ->first();

        if ($specificDate !== null && $specificDate->shift !== null) {
            return $this->buildResolvedShift($specificDate->shift, $civilDate, 'specific_date', $specificDate);
        }

        // 4. Check Date-Range Assignment
        $dateRange = HrShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('assignment_type', ShiftAssignmentType::DateRange)
            ->whereDate('start_date', '<=', $civilDate)
            ->whereDate('end_date', '>=', $civilDate)
            ->where('is_cancelled', false)
            ->with('shift')
            ->first();

        if ($dateRange !== null && $dateRange->shift !== null) {
            return $this->buildResolvedShift($dateRange->shift, $civilDate, 'date_range', $dateRange);
        }

        // 5. Check Recurring Weekly Assignment (1=Mon ... 7=Sun)
        $dayOfWeek = (int) $date->dayOfWeekIso;
        $recurring = HrShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('assignment_type', ShiftAssignmentType::Recurring)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_cancelled', false)
            ->with('shift')
            ->first();

        if ($recurring !== null && $recurring->shift !== null) {
            return $this->buildResolvedShift($recurring->shift, $civilDate, 'recurring', $recurring);
        }

        // 6. Check Public Holiday
        $holiday = HrHoliday::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('holiday_date', $civilDate)
            ->first();

        if ($holiday !== null) {
            return new ResolvedShiftData(
                shift: null,
                shiftDate: $civilDate,
                isWorkingDay: false,
                isRestDay: false,
                isHoliday: true,
                holidayName: $holiday->name,
                startTime: null,
                endTime: null,
                isOvernight: false,
                scheduledMinutes: 0,
                graceInMinutes: 0,
                graceOutMinutes: 0,
                resolutionSource: 'holiday',
            );
        }

        // 7. Default Rest Day
        return new ResolvedShiftData(
            shift: null,
            shiftDate: $civilDate,
            isWorkingDay: false,
            isRestDay: true,
            isHoliday: false,
            holidayName: null,
            startTime: null,
            endTime: null,
            isOvernight: false,
            scheduledMinutes: 0,
            graceInMinutes: 0,
            graceOutMinutes: 0,
            resolutionSource: 'rest_day',
        );
    }

    private function buildResolvedShift(
        HrShift $shift,
        string $civilDate,
        string $source,
        ?HrShiftAssignment $assignment = null,
    ): ResolvedShiftData {
        $startTime = ($assignment !== null && $assignment->is_custom_timing && $assignment->custom_start_time !== null)
            ? (string) $assignment->custom_start_time
            : (string) $shift->start_time;

        $endTime = ($assignment !== null && $assignment->is_custom_timing && $assignment->custom_end_time !== null)
            ? (string) $assignment->custom_end_time
            : (string) $shift->end_time;

        // Calculate scheduled minutes
        $start = CarbonImmutable::parse("{$civilDate} {$startTime}");
        $end = CarbonImmutable::parse("{$civilDate} {$endTime}");
        if ($shift->is_overnight || $end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }
        $scheduledMinutes = (int) $start->diffInMinutes($end);

        return new ResolvedShiftData(
            shift: $shift,
            shiftDate: $civilDate,
            isWorkingDay: true,
            isRestDay: false,
            isHoliday: false,
            holidayName: null,
            startTime: $startTime,
            endTime: $endTime,
            isOvernight: (bool) $shift->is_overnight,
            scheduledMinutes: $scheduledMinutes,
            graceInMinutes: (int) $shift->grace_in_minutes,
            graceOutMinutes: (int) $shift->grace_out_minutes,
            resolutionSource: $source,
        );
    }
}
