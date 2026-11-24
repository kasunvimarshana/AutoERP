<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\ShiftAssignmentData;
use Modules\Hr\DTOs\ShiftData;
use Modules\Hr\DTOs\ShiftReallocationData;
use Modules\Hr\DTOs\ShiftSwapData;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Enums\ShiftReallocationReason;
use Modules\Hr\Enums\ShiftStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Models\HrShiftSwap;
use Modules\Hr\Services\ShiftRosterService;

final class HrShiftController
{
    public function __construct(
        private readonly ShiftRosterService $rosterService,
    ) {}

    public function index(TenantScopedRequest $request): JsonResponse
    {
        $shifts = HrShift::query()
            ->where('tenant_id', $request->tenantId())
            ->with('breaks')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $shifts]);
    }

    public function store(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:16'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'is_overnight' => ['nullable', 'boolean'],
            'grace_in_minutes' => ['nullable', 'integer', 'min:0'],
            'grace_out_minutes' => ['nullable', 'integer', 'min:0'],
            'half_day_threshold_minutes' => ['nullable', 'integer', 'min:0'],
            'full_day_threshold_minutes' => ['nullable', 'integer', 'min:0'],
            'is_break_paid' => ['nullable', 'boolean'],
            'auto_deduct_break' => ['nullable', 'boolean'],
            'auto_deduct_break_minutes' => ['nullable', 'integer', 'min:0'],
            'auto_deduct_threshold_minutes' => ['nullable', 'integer', 'min:0'],
            'is_flexible' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = new ShiftData(
            code: $validated['code'],
            name: $validated['name'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            color: $validated['color'] ?? '#3B82F6',
            isOvernight: (bool) ($validated['is_overnight'] ?? false),
            graceInMinutes: (int) ($validated['grace_in_minutes'] ?? 15),
            graceOutMinutes: (int) ($validated['grace_out_minutes'] ?? 15),
            halfDayThresholdMinutes: (int) ($validated['half_day_threshold_minutes'] ?? 240),
            fullDayThresholdMinutes: (int) ($validated['full_day_threshold_minutes'] ?? 480),
            isBreakPaid: (bool) ($validated['is_break_paid'] ?? false),
            autoDeductBreak: (bool) ($validated['auto_deduct_break'] ?? false),
            autoDeductBreakMinutes: (int) ($validated['auto_deduct_break_minutes'] ?? 0),
            autoDeductThresholdMinutes: (int) ($validated['auto_deduct_threshold_minutes'] ?? 300),
            isFlexible: (bool) ($validated['is_flexible'] ?? false),
            status: isset($validated['status']) ? ShiftStatus::from($validated['status']) : ShiftStatus::Active,
            notes: $validated['notes'] ?? null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $shift = $this->rosterService->createShift($request->tenantId(), $data);

        return response()->json(['data' => $shift], 201);
    }

    public function show(TenantScopedRequest $request, int $shift): JsonResponse
    {
        $shiftModel = HrShift::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $shift)
            ->with('breaks')
            ->firstOrFail();

        return response()->json(['data' => $shiftModel]);
    }

    public function update(TenantScopedRequest $request, int $shift): JsonResponse
    {
        $shiftModel = HrShift::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $shift)
            ->firstOrFail();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:16'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'is_overnight' => ['nullable', 'boolean'],
            'grace_in_minutes' => ['nullable', 'integer', 'min:0'],
            'grace_out_minutes' => ['nullable', 'integer', 'min:0'],
            'half_day_threshold_minutes' => ['nullable', 'integer', 'min:0'],
            'full_day_threshold_minutes' => ['nullable', 'integer', 'min:0'],
            'is_break_paid' => ['nullable', 'boolean'],
            'auto_deduct_break' => ['nullable', 'boolean'],
            'auto_deduct_break_minutes' => ['nullable', 'integer', 'min:0'],
            'auto_deduct_threshold_minutes' => ['nullable', 'integer', 'min:0'],
            'is_flexible' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = new ShiftData(
            code: $validated['code'],
            name: $validated['name'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            color: $validated['color'] ?? $shiftModel->color,
            isOvernight: (bool) ($validated['is_overnight'] ?? $shiftModel->is_overnight),
            graceInMinutes: (int) ($validated['grace_in_minutes'] ?? $shiftModel->grace_in_minutes),
            graceOutMinutes: (int) ($validated['grace_out_minutes'] ?? $shiftModel->grace_out_minutes),
            halfDayThresholdMinutes: (int) ($validated['half_day_threshold_minutes'] ?? $shiftModel->half_day_threshold_minutes),
            fullDayThresholdMinutes: (int) ($validated['full_day_threshold_minutes'] ?? $shiftModel->full_day_threshold_minutes),
            isBreakPaid: (bool) ($validated['is_break_paid'] ?? $shiftModel->is_break_paid),
            autoDeductBreak: (bool) ($validated['auto_deduct_break'] ?? $shiftModel->auto_deduct_break),
            autoDeductBreakMinutes: (int) ($validated['auto_deduct_break_minutes'] ?? $shiftModel->auto_deduct_break_minutes),
            autoDeductThresholdMinutes: (int) ($validated['auto_deduct_threshold_minutes'] ?? $shiftModel->auto_deduct_threshold_minutes),
            isFlexible: (bool) ($validated['is_flexible'] ?? $shiftModel->is_flexible),
            status: isset($validated['status']) ? ShiftStatus::from($validated['status']) : $shiftModel->status,
            notes: $validated['notes'] ?? $shiftModel->notes,
            organizationUnitId: $request->organizationUnitId(),
        );

        $updated = $this->rosterService->updateShift($shiftModel, $data);

        return response()->json(['data' => $updated]);
    }

    public function destroy(TenantScopedRequest $request, int $shift): JsonResponse
    {
        $shiftModel = HrShift::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $shift)
            ->firstOrFail();

        $shiftModel->delete();

        return response()->json(null, 204);
    }

    public function assign(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'shift_id' => ['required', 'integer'],
            'assignment_type' => ['required', 'string'],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_custom_timing' => ['nullable', 'boolean'],
            'custom_start_time' => ['nullable', 'string'],
            'custom_end_time' => ['nullable', 'string'],
        ]);

        $data = new ShiftAssignmentData(
            employeeId: (int) $validated['employee_id'],
            shiftId: (int) $validated['shift_id'],
            assignmentType: ShiftAssignmentType::from($validated['assignment_type']),
            dayOfWeek: isset($validated['day_of_week']) ? (int) $validated['day_of_week'] : null,
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            isCustomTiming: (bool) ($validated['is_custom_timing'] ?? false),
            customStartTime: $validated['custom_start_time'] ?? null,
            customEndTime: $validated['custom_end_time'] ?? null,
            assignedBy: $request->currentUserId(),
            organizationUnitId: $request->organizationUnitId(),
        );

        $assignment = $this->rosterService->assignShift($request->tenantId(), $data);

        return response()->json(['data' => $assignment], 201);
    }

    public function cancelAssignment(TenantScopedRequest $request, int $assignment): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $assignmentModel = HrShiftAssignment::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $assignment)
            ->firstOrFail();

        $cancelled = $this->rosterService->cancelAssignment($assignmentModel, $validated['reason']);

        return response()->json(['data' => $cancelled]);
    }

    public function roster(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
            'department_id' => ['nullable', 'integer'],
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $empQuery = HrEmployee::query()->where('tenant_id', $request->tenantId())->active();
        if (! empty($validated['employee_ids'])) {
            $empQuery->whereIn('id', $validated['employee_ids']);
        }
        if (! empty($validated['department_id'])) {
            $empQuery->where('department_id', $validated['department_id']);
        }
        $employees = $empQuery->select(['id', 'employee_number', 'display_name', 'department_id'])->get();

        $roster = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($employees as $emp) {
            $empDays = [];
            foreach ($period as $day) {
                $dateStr = $day->toDateString();
                $resolved = $this->rosterService->resolveShift($request->tenantId(), (int) $emp->id, $dateStr);
                $empDays[$dateStr] = [
                    'shift' => $resolved->shift ? [
                        'id' => $resolved->shift->id,
                        'name' => $resolved->shift->name,
                        'code' => $resolved->shift->code,
                        'color' => $resolved->shift->color,
                        'start_time' => $resolved->startTime,
                        'end_time' => $resolved->endTime,
                    ] : null,
                    'is_working' => $resolved->isWorkingDay,
                    'is_rest_day' => $resolved->isRestDay,
                    'is_holiday' => $resolved->isHoliday,
                    'holiday_name' => $resolved->holidayName,
                    'source' => $resolved->resolutionSource,
                ];
            }
            $roster[] = [
                'employee' => [
                    'id' => $emp->id,
                    'employee_number' => $emp->employee_number,
                    'display_name' => $emp->display_name,
                ],
                'days' => $empDays,
            ];
        }

        return response()->json(['data' => $roster]);
    }

    public function requestSwap(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'requester_employee_id' => ['required', 'integer'],
            'target_employee_id' => ['required', 'integer'],
            'swap_date' => ['required', 'date'],
            'target_swap_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
        ]);

        $data = new ShiftSwapData(
            requesterEmployeeId: (int) $validated['requester_employee_id'],
            targetEmployeeId: (int) $validated['target_employee_id'],
            swapDate: $validated['swap_date'],
            targetSwapDate: $validated['target_swap_date'],
            reason: $validated['reason'],
            organizationUnitId: $request->organizationUnitId(),
        );

        $swap = $this->rosterService->requestSwap($request->tenantId(), $data);

        return response()->json(['data' => $swap], 201);
    }

    public function peerActionSwap(TenantScopedRequest $request, int $swap): JsonResponse
    {
        $validated = $request->validate([
            'peer_employee_id' => ['required', 'integer'],
            'accept' => ['required', 'boolean'],
        ]);

        $swapModel = HrShiftSwap::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $swap)
            ->firstOrFail();

        $updated = $this->rosterService->peerActionSwap($swapModel, (int) $validated['peer_employee_id'], (bool) $validated['accept']);

        return response()->json(['data' => $updated]);
    }

    public function managerActionSwap(TenantScopedRequest $request, int $swap): JsonResponse
    {
        $validated = $request->validate([
            'approve' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $swapModel = HrShiftSwap::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $swap)
            ->firstOrFail();

        $updated = $this->rosterService->managerActionSwap(
            $swapModel,
            (int) $request->currentUserId(),
            (bool) $validated['approve'],
            $validated['notes'] ?? null,
        );

        return response()->json(['data' => $updated]);
    }

    public function reallocateCover(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'absent_employee_id' => ['required', 'integer'],
            'cover_employee_id' => ['required', 'integer'],
            'shift_id' => ['required', 'integer'],
            'reallocation_date' => ['required', 'date'],
            'reason_code' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = new ShiftReallocationData(
            absentEmployeeId: (int) $validated['absent_employee_id'],
            coverEmployeeId: (int) $validated['cover_employee_id'],
            shiftId: (int) $validated['shift_id'],
            reallocationDate: $validated['reallocation_date'],
            reasonCode: isset($validated['reason_code']) ? ShiftReallocationReason::from($validated['reason_code']) : ShiftReallocationReason::SickCover,
            notes: $validated['notes'] ?? null,
            reallocatedBy: $request->currentUserId(),
            organizationUnitId: $request->organizationUnitId(),
        );

        $reallocation = $this->rosterService->reallocateCover($request->tenantId(), $data);

        return response()->json(['data' => $reallocation], 201);
    }
}
