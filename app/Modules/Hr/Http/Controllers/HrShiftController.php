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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\ShiftAssignmentData;
use Modules\Hr\DTOs\ShiftData;
use Modules\Hr\DTOs\ShiftReallocationData;
use Modules\Hr\DTOs\ShiftSwapData;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Enums\ShiftReallocationReason;
use Modules\Hr\Enums\ShiftStatus;
use Modules\Hr\Enums\ShiftSwapStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Models\HrShiftReallocation;
use Modules\Hr\Models\HrShiftRotation;
use Modules\Hr\Models\HrShiftRotationAssignment;
use Modules\Hr\Models\HrShiftRotationStep;
use Modules\Hr\Models\HrShiftSwap;
use Modules\Hr\Services\ShiftRosterService;

final class HrShiftController
{
    public function __construct(
        private readonly ShiftRosterService $rosterService,
    ) {}

    public function index(TenantScopedRequest $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 25)));

        $shifts = HrShift::query()
            ->where('tenant_id', $request->tenantId())
            ->with('breaks')
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($shifts);
    }

    public function store(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('hr_shifts', 'code')->where('tenant_id', $tenantId)],
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
            'status' => ['nullable', 'string', Rule::enum(ShiftStatus::class)],
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
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:32', Rule::unique('hr_shifts', 'code')->where('tenant_id', $tenantId)->ignore($shift)],
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
            'status' => ['nullable', 'string', Rule::enum(ShiftStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $shift, $validated, $expectedVersion): ?HrShift {
            $shiftModel = HrShift::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $shift)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $shiftModel->row_version !== $expectedVersion) {
                return null;
            }

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
                notes: $request->has('notes') ? ($validated['notes'] ?? null) : $shiftModel->notes,
                organizationUnitId: $request->organizationUnitId(),
            );

            return $this->rosterService->updateShift($shiftModel, $data);
        });

        if ($updated === null) {
            return response()->json(['message' => 'Shift was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroy(TenantScopedRequest $request, int $shift): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $result = DB::transaction(function () use ($request, $shift, $expectedVersion): string {
            $tenantId = $request->tenantId();
            $shiftModel = HrShift::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $shift)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $shiftModel->row_version !== $expectedVersion) {
                return 'conflict';
            }

            $inUse = HrShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift)
                ->exists()
                || HrShiftReallocation::query()
                    ->where('tenant_id', $tenantId)
                    ->where('shift_id', $shift)
                    ->exists()
                || DB::table('hr_shift_rotation_steps')
                    ->where('tenant_id', $tenantId)
                    ->where('shift_id', $shift)
                    ->exists()
                || DB::table('hr_attendance_records')
                    ->where('tenant_id', $tenantId)
                    ->where('shift_id', $shift)
                    ->exists();

            if ($inUse) {
                return 'in_use';
            }

            $shiftModel->delete();

            return 'deleted';
        });

        if ($result === 'conflict') {
            return response()->json(['message' => 'Shift was changed by someone else. Reload before deleting.'], 409);
        }

        if ($result === 'in_use') {
            return response()->json(['message' => 'Cannot delete shift because it is referenced by assignments, rotations, reallocations, or attendance records.'], 422);
        }

        return response()->json(null, 204);
    }

    public function assignments(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'shift_id' => ['nullable', 'integer'],
            'is_cancelled' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrShiftAssignment::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['employee', 'shift'])
            ->orderByDesc('created_at');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', (int) $validated['employee_id']);
        }
        if (! empty($validated['shift_id'])) {
            $query->where('shift_id', (int) $validated['shift_id']);
        }
        if ($request->has('is_cancelled')) {
            $query->where('is_cancelled', (bool) $validated['is_cancelled']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function assign(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['required', 'integer', Rule::exists('hr_shifts', 'id')->where('tenant_id', $tenantId)],
            'assignment_type' => ['required', 'string', Rule::enum(ShiftAssignmentType::class)],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
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
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $cancelled = DB::transaction(function () use ($request, $assignment, $validated, $expectedVersion): ?HrShiftAssignment {
            $assignmentModel = HrShiftAssignment::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $assignment)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $assignmentModel->row_version !== $expectedVersion) {
                return null;
            }

            return $this->rosterService->cancelAssignment($assignmentModel, $validated['reason']);
        });

        if ($cancelled === null) {
            return response()->json(['message' => 'Shift assignment was changed by someone else. Reload before cancelling.'], 409);
        }

        return response()->json(['data' => $cancelled]);
    }

    public function roster(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
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
        $employeeIds = $employees->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
        $resolvedBatch = $this->rosterService->resolveRosterBatch($request->tenantId(), $employeeIds, $startDate, $endDate);

        foreach ($employees as $emp) {
            $empId = (int) $emp->id;
            $empDays = [];
            foreach ($period as $day) {
                $dateStr = $day->toDateString();
                $resolved = $resolvedBatch[$empId][$dateStr] ?? null;
                if ($resolved === null) {
                    continue;
                }
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

    public function swaps(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::enum(ShiftSwapStatus::class)],
            'employee_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrShiftSwap::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['requester', 'targetEmployee', 'requesterShift', 'targetShift'])
            ->orderByDesc('created_at');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['employee_id'])) {
            $empId = (int) $validated['employee_id'];
            $query->where(fn ($q) => $q->where('requester_employee_id', $empId)->orWhere('target_employee_id', $empId));
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function requestSwap(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'requester_employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'target_employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
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
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'peer_employee_id' => ['required', 'integer'],
            'accept' => ['required', 'boolean'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $swap, $validated, $expectedVersion): ?HrShiftSwap {
            $swapModel = HrShiftSwap::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $swap)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $swapModel->row_version !== $expectedVersion) {
                return null;
            }

            return $this->rosterService->peerActionSwap($swapModel, (int) $validated['peer_employee_id'], (bool) $validated['accept']);
        });

        if ($updated === null) {
            return response()->json(['message' => 'Shift swap was changed by someone else. Reload before acting.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function managerActionSwap(TenantScopedRequest $request, int $swap): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'approve' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $swap, $validated, $expectedVersion): ?HrShiftSwap {
            $swapModel = HrShiftSwap::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $swap)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $swapModel->row_version !== $expectedVersion) {
                return null;
            }

            return $this->rosterService->managerActionSwap(
                $swapModel,
                (int) $request->currentUserId(),
                (bool) $validated['approve'],
                $validated['notes'] ?? null,
            );
        });

        if ($updated === null) {
            return response()->json(['message' => 'Shift swap was changed by someone else. Reload before acting.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function reallocations(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'reallocation_date' => ['nullable', 'date'],
            'absent_employee_id' => ['nullable', 'integer'],
            'cover_employee_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrShiftReallocation::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['absentEmployee', 'coverEmployee', 'shift'])
            ->orderByDesc('reallocation_date');

        if (! empty($validated['reallocation_date'])) {
            $query->whereDate('reallocation_date', $validated['reallocation_date']);
        }
        if (! empty($validated['absent_employee_id'])) {
            $query->where('absent_employee_id', (int) $validated['absent_employee_id']);
        }
        if (! empty($validated['cover_employee_id'])) {
            $query->where('cover_employee_id', (int) $validated['cover_employee_id']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function reallocateCover(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'absent_employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'cover_employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['required', 'integer', Rule::exists('hr_shifts', 'id')->where('tenant_id', $tenantId)],
            'reallocation_date' => ['required', 'date'],
            'reason_code' => ['nullable', 'string', Rule::enum(ShiftReallocationReason::class)],
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

    public function indexRotations(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrShiftRotation::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['steps.shift'])
            ->orderBy('name');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function storeRotation(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('hr_shift_rotations', 'code')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'cycle_days' => ['required', 'integer', 'between:1,365'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $rotation = $this->rosterService->createRotation($tenantId, [
            'organization_unit_id' => $request->organizationUnitId(),
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'cycle_days' => (int) $validated['cycle_days'],
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json(['data' => $rotation->load('steps.shift')], 201);
    }

    public function showRotation(TenantScopedRequest $request, int $rotation): JsonResponse
    {
        $rotationModel = HrShiftRotation::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $rotation)
            ->with(['steps.shift'])
            ->firstOrFail();

        return response()->json(['data' => $rotationModel]);
    }

    public function updateRotation(TenantScopedRequest $request, int $rotation): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'code' => ['sometimes', 'required', 'string', 'max:64', Rule::unique('hr_shift_rotations', 'code')->where('tenant_id', $tenantId)->ignore($rotation)],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'cycle_days' => ['sometimes', 'required', 'integer', 'between:1,365'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $tenantId, $rotation, $validated, $expectedVersion): ?HrShiftRotation {
            $rotationModel = HrShiftRotation::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $rotation)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $rotationModel->row_version !== $expectedVersion) {
                return null;
            }

            $rotationModel->update([
                'code' => $request->has('code') ? $validated['code'] : $rotationModel->code,
                'name' => $request->has('name') ? $validated['name'] : $rotationModel->name,
                'description' => $request->has('description') ? ($validated['description'] ?? null) : $rotationModel->description,
                'cycle_days' => $request->has('cycle_days') ? (int) $validated['cycle_days'] : $rotationModel->cycle_days,
                'status' => $request->has('status') ? $validated['status'] : $rotationModel->status,
                'row_version' => ((int) $rotationModel->row_version) + 1,
            ]);

            return $rotationModel->fresh('steps.shift');
        });

        if ($updated === null) {
            return response()->json(['message' => 'Shift rotation was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroyRotation(TenantScopedRequest $request, int $rotation): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $result = DB::transaction(function () use ($request, $rotation, $expectedVersion): string {
            $tenantId = $request->tenantId();
            $rotationModel = HrShiftRotation::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $rotation)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $rotationModel->row_version !== $expectedVersion) {
                return 'conflict';
            }

            $inUse = HrShiftRotationAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_rotation_id', $rotation)
                ->exists();

            if ($inUse) {
                return 'in_use';
            }

            $rotationModel->delete();

            return 'deleted';
        });

        if ($result === 'conflict') {
            return response()->json(['message' => 'Shift rotation was changed by someone else. Reload before deleting.'], 409);
        }

        if ($result === 'in_use') {
            return response()->json(['message' => 'Cannot delete shift rotation because it is referenced by rotation assignments.'], 422);
        }

        return response()->json(null, 204);
    }

    public function storeRotationStep(TenantScopedRequest $request, int $rotation): JsonResponse
    {
        $tenantId = $request->tenantId();
        $rotationModel = HrShiftRotation::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rotation)
            ->firstOrFail();

        $validated = $request->validate([
            'day_sequence' => [
                'required',
                'integer',
                'between:1,'.$rotationModel->cycle_days,
                Rule::unique('hr_shift_rotation_steps', 'day_sequence')
                    ->where('tenant_id', $tenantId)
                    ->where('shift_rotation_id', $rotationModel->id),
            ],
            'shift_id' => ['nullable', 'integer', Rule::exists('hr_shifts', 'id')->where('tenant_id', $tenantId)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $step = $this->rosterService->addRotationStep(
            $rotationModel,
            (int) $validated['day_sequence'],
            isset($validated['shift_id']) ? (int) $validated['shift_id'] : null,
            $validated['notes'] ?? null,
        );

        return response()->json(['data' => $step->load('shift')], 201);
    }

    public function destroyRotationStep(TenantScopedRequest $request, int $rotation, int $step): JsonResponse
    {
        $tenantId = $request->tenantId();
        $stepModel = HrShiftRotationStep::query()
            ->where('tenant_id', $tenantId)
            ->where('shift_rotation_id', $rotation)
            ->where('id', $step)
            ->firstOrFail();

        $stepModel->delete();

        return response()->json(null, 204);
    }

    public function rotationAssignments(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'shift_rotation_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrShiftRotationAssignment::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['employee', 'rotation'])
            ->orderByDesc('anchor_date');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', (int) $validated['employee_id']);
        }
        if (! empty($validated['shift_rotation_id'])) {
            $query->where('shift_rotation_id', (int) $validated['shift_rotation_id']);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $validated['is_active']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function assignRotation(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'shift_rotation_id' => ['required', 'integer', Rule::exists('hr_shift_rotations', 'id')->where('tenant_id', $tenantId)],
            'anchor_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:anchor_date'],
        ]);

        try {
            $assignment = $this->rosterService->assignRotation(
                $tenantId,
                (int) $validated['employee_id'],
                (int) $validated['shift_rotation_id'],
                $validated['anchor_date'],
                $validated['end_date'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $assignment->load(['employee', 'rotation'])], 201);
    }

    public function cancelRotationAssignment(TenantScopedRequest $request, int $assignment): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $cancelled = DB::transaction(function () use ($request, $assignment, $expectedVersion): ?HrShiftRotationAssignment {
            $assignmentModel = HrShiftRotationAssignment::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $assignment)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $assignmentModel->row_version !== $expectedVersion) {
                return null;
            }

            $assignmentModel->update([
                'is_active' => false,
                'row_version' => ((int) $assignmentModel->row_version) + 1,
            ]);

            return $assignmentModel->fresh();
        });

        if ($cancelled === null) {
            return response()->json(['message' => 'Rotation assignment was changed by someone else. Reload before cancelling.'], 409);
        }

        return response()->json(['data' => $cancelled]);
    }
}
