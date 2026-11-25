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
use Carbon\CarbonPeriod;
use InvalidArgumentException;
use Modules\Hr\DTOs\ResolvedShiftData;
use Modules\Hr\DTOs\ShiftAssignmentData;
use Modules\Hr\DTOs\ShiftData;
use Modules\Hr\DTOs\ShiftReallocationData;
use Modules\Hr\DTOs\ShiftSwapData;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Enums\ShiftSwapStatus;
use Modules\Hr\Models\HrHoliday;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Models\HrShiftReallocation;
use Modules\Hr\Models\HrShiftRotation;
use Modules\Hr\Models\HrShiftRotationAssignment;
use Modules\Hr\Models\HrShiftRotationStep;
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
        // 1. Validate Overlapping Active Shift Assignments
        if ($data->assignmentType === ShiftAssignmentType::SpecificDate && $data->startDate !== null) {
            $hasOverlap = HrShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->where('assignment_type', ShiftAssignmentType::SpecificDate)
                ->whereDate('start_date', $data->startDate)
                ->where('is_cancelled', false)
                ->exists();

            if ($hasOverlap) {
                throw new InvalidArgumentException(sprintf('Employee already has an active specific-date shift assignment on %s.', $data->startDate));
            }

            // Check Leave Conflict
            $hasLeave = HrLeaveRequest::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->whereDate('start_date', '<=', $data->startDate)
                ->whereDate('end_date', '>=', $data->startDate)
                ->where('status', LeaveRequestStatus::Approved)
                ->exists();

            if ($hasLeave) {
                throw new InvalidArgumentException(sprintf('Cannot assign shift: Employee has approved leave on %s.', $data->startDate));
            }
        } elseif ($data->assignmentType === ShiftAssignmentType::DateRange && $data->startDate !== null && $data->endDate !== null) {
            $hasOverlap = HrShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->where('assignment_type', ShiftAssignmentType::DateRange)
                ->whereDate('start_date', '<=', $data->endDate)
                ->whereDate('end_date', '>=', $data->startDate)
                ->where('is_cancelled', false)
                ->exists();

            if ($hasOverlap) {
                throw new InvalidArgumentException('Employee already has an active overlapping date-range shift assignment.');
            }
        } elseif ($data->assignmentType === ShiftAssignmentType::Recurring && $data->dayOfWeek !== null) {
            $hasOverlap = HrShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $data->employeeId)
                ->where('assignment_type', ShiftAssignmentType::Recurring)
                ->where('day_of_week', $data->dayOfWeek)
                ->where('is_cancelled', false)
                ->exists();

            if ($hasOverlap) {
                throw new InvalidArgumentException(sprintf('Employee already has an active recurring shift assignment for day %d.', $data->dayOfWeek));
            }
        }

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

    public function createRotation(int $tenantId, array $data): HrShiftRotation
    {
        return HrShiftRotation::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data['organization_unit_id'] ?? null,
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'cycle_days' => (int) ($data['cycle_days'] ?? 7),
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function addRotationStep(HrShiftRotation $rotation, int $daySequence, ?int $shiftId, ?string $notes = null): HrShiftRotationStep
    {
        return HrShiftRotationStep::create([
            'tenant_id' => $rotation->tenant_id,
            'organization_unit_id' => $rotation->organization_unit_id,
            'shift_rotation_id' => $rotation->id,
            'day_sequence' => $daySequence,
            'shift_id' => $shiftId,
            'notes' => $notes,
        ]);
    }

    public function assignRotation(int $tenantId, int $employeeId, int $rotationId, string $anchorDate, ?string $endDate = null): HrShiftRotationAssignment
    {
        return HrShiftRotationAssignment::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'employee_id' => $employeeId,
            'shift_rotation_id' => $rotationId,
            'anchor_date' => $anchorDate,
            'end_date' => $endDate,
            'is_active' => true,
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

        // 4b. Check Shift Rotation Assignment
        $rotationAssignment = HrShiftRotationAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('is_active', true)
            ->whereDate('anchor_date', '<=', $civilDate)
            ->where(function ($q) use ($civilDate): void {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $civilDate);
            })
            ->with(['rotation.steps.shift'])
            ->first();

        if ($rotationAssignment !== null && $rotationAssignment->rotation !== null) {
            $anchor = CarbonImmutable::parse($rotationAssignment->anchor_date);
            $dayDiff = $anchor->diffInDays($date);
            $cycleDays = max(1, (int) $rotationAssignment->rotation->cycle_days);
            $stepSequence = ($dayDiff % $cycleDays) + 1;

            $step = $rotationAssignment->rotation->steps->firstWhere('day_sequence', $stepSequence);
            if ($step !== null) {
                if ($step->shift !== null) {
                    return $this->buildResolvedShift($step->shift, $civilDate, 'rotation');
                }

                // Scheduled rest day in rotation
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
                    resolutionSource: 'rotation_rest',
                );
            }
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

    /**
     * Batch-resolve shifts for a set of employees across a date range using preloaded data (no N+1 queries).
     *
     * @param  list<int>  $employeeIds
     * @return array<int, array<string, ResolvedShiftData>>
     */
    public function resolveRosterBatch(int $tenantId, array $employeeIds, string $startDate, string $endDate): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $reallocations = HrShiftReallocation::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('cover_employee_id', $employeeIds)
            ->whereDate('reallocation_date', '>=', $startDate)
            ->whereDate('reallocation_date', '<=', $endDate)
            ->with('shift')
            ->get()
            ->groupBy(static fn (HrShiftReallocation $item): string => $item->cover_employee_id.'|'.CarbonImmutable::parse($item->reallocation_date)->toDateString());

        $swapsAsRequester = HrShiftSwap::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('requester_employee_id', $employeeIds)
            ->whereDate('swap_date', '>=', $startDate)
            ->whereDate('swap_date', '<=', $endDate)
            ->where('status', ShiftSwapStatus::ManagerApproved)
            ->with('targetShift')
            ->get()
            ->groupBy(static fn (HrShiftSwap $item): string => $item->requester_employee_id.'|'.CarbonImmutable::parse($item->swap_date)->toDateString());

        $swapsAsTarget = HrShiftSwap::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('target_employee_id', $employeeIds)
            ->whereDate('target_swap_date', '>=', $startDate)
            ->whereDate('target_swap_date', '<=', $endDate)
            ->where('status', ShiftSwapStatus::ManagerApproved)
            ->with('requesterShift')
            ->get()
            ->groupBy(static fn (HrShiftSwap $item): string => $item->target_employee_id.'|'.CarbonImmutable::parse($item->target_swap_date)->toDateString());

        $assignments = HrShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('employee_id', $employeeIds)
            ->where('is_cancelled', false)
            ->with('shift')
            ->get()
            ->groupBy('employee_id');

        $rotationAssignments = HrShiftRotationAssignment::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('employee_id', $employeeIds)
            ->where('is_active', true)
            ->whereDate('anchor_date', '<=', $endDate)
            ->where(static function ($q) use ($startDate): void {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $startDate);
            })
            ->with(['rotation.steps.shift'])
            ->get()
            ->groupBy('employee_id');

        $holidaysByDate = HrHoliday::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('holiday_date', '>=', $startDate)
            ->whereDate('holiday_date', '<=', $endDate)
            ->get()
            ->keyBy(static fn (HrHoliday $holiday): string => CarbonImmutable::parse($holiday->holiday_date)->toDateString());

        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $day) {
            $dates[] = $day->toDateString();
        }

        $resolvedByEmployee = [];

        foreach ($employeeIds as $employeeId) {
            $empAssignments = $assignments->get($employeeId, collect());
            $empRotations = $rotationAssignments->get($employeeId, collect());
            $empDays = [];

            foreach ($dates as $civilDate) {
                $key = $employeeId.'|'.$civilDate;
                $date = CarbonImmutable::parse($civilDate);

                // 1. Shift Reallocation
                $reallocation = $reallocations->get($key)?->first();
                if ($reallocation !== null && $reallocation->shift !== null) {
                    $empDays[$civilDate] = $this->buildResolvedShift($reallocation->shift, $civilDate, 'reallocation');

                    continue;
                }

                // 2. Approved Shift Swap
                $swapReq = $swapsAsRequester->get($key)?->first();
                if ($swapReq !== null && $swapReq->targetShift !== null) {
                    $empDays[$civilDate] = $this->buildResolvedShift($swapReq->targetShift, $civilDate, 'swap');

                    continue;
                }

                $swapTgt = $swapsAsTarget->get($key)?->first();
                if ($swapTgt !== null && $swapTgt->requesterShift !== null) {
                    $empDays[$civilDate] = $this->buildResolvedShift($swapTgt->requesterShift, $civilDate, 'swap');

                    continue;
                }

                // 3. Specific-Date Override
                $specificDate = $empAssignments->first(static fn (HrShiftAssignment $a): bool => $a->assignment_type === ShiftAssignmentType::SpecificDate
                    && $a->start_date !== null
                    && CarbonImmutable::parse($a->start_date)->toDateString() === $civilDate);
                if ($specificDate !== null && $specificDate->shift !== null) {
                    $empDays[$civilDate] = $this->buildResolvedShift($specificDate->shift, $civilDate, 'specific_date', $specificDate);

                    continue;
                }

                // 4. Date-Range Assignment
                $dateRange = $empAssignments->first(static fn (HrShiftAssignment $a): bool => $a->assignment_type === ShiftAssignmentType::DateRange
                    && $a->start_date !== null
                    && $a->end_date !== null
                    && CarbonImmutable::parse($a->start_date)->toDateString() <= $civilDate
                    && CarbonImmutable::parse($a->end_date)->toDateString() >= $civilDate);
                if ($dateRange !== null && $dateRange->shift !== null) {
                    $empDays[$civilDate] = $this->buildResolvedShift($dateRange->shift, $civilDate, 'date_range', $dateRange);

                    continue;
                }

                // 4b. Shift Rotation Assignment
                $rotationAssignment = $empRotations->first(static fn (HrShiftRotationAssignment $r): bool => CarbonImmutable::parse($r->anchor_date)->toDateString() <= $civilDate
                    && ($r->end_date === null || CarbonImmutable::parse($r->end_date)->toDateString() >= $civilDate));
                if ($rotationAssignment !== null && $rotationAssignment->rotation !== null) {
                    $anchor = CarbonImmutable::parse($rotationAssignment->anchor_date);
                    $dayDiff = $anchor->diffInDays($date);
                    $cycleDays = max(1, (int) $rotationAssignment->rotation->cycle_days);
                    $stepSequence = ($dayDiff % $cycleDays) + 1;

                    $step = $rotationAssignment->rotation->steps->firstWhere('day_sequence', $stepSequence);
                    if ($step !== null) {
                        if ($step->shift !== null) {
                            $empDays[$civilDate] = $this->buildResolvedShift($step->shift, $civilDate, 'rotation');

                            continue;
                        }

                        $empDays[$civilDate] = new ResolvedShiftData(
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
                            resolutionSource: 'rotation_rest',
                        );

                        continue;
                    }
                }

                // 5. Recurring Weekly Assignment (1=Mon ... 7=Sun)
                $dayOfWeek = (int) $date->dayOfWeekIso;
                $recurring = $empAssignments->first(static fn (HrShiftAssignment $a): bool => $a->assignment_type === ShiftAssignmentType::Recurring
                    && (int) $a->day_of_week === $dayOfWeek);
                if ($recurring !== null && $recurring->shift !== null) {
                    $empDays[$civilDate] = $this->buildResolvedShift($recurring->shift, $civilDate, 'recurring', $recurring);

                    continue;
                }

                // 6. Public Holiday
                $holiday = $holidaysByDate->get($civilDate);
                if ($holiday !== null) {
                    $empDays[$civilDate] = new ResolvedShiftData(
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

                    continue;
                }

                // 7. Default Rest Day
                $empDays[$civilDate] = new ResolvedShiftData(
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

            $resolvedByEmployee[$employeeId] = $empDays;
        }

        return $resolvedByEmployee;
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
