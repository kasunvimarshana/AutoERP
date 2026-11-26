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

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\ShiftAssignmentData;
use Modules\Hr\DTOs\ShiftData;
use Modules\Hr\DTOs\ShiftReallocationData;
use Modules\Hr\DTOs\ShiftSwapData;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\ShiftAssignmentType;
use Modules\Hr\Enums\ShiftReallocationReason;
use Modules\Hr\Enums\ShiftStatus;
use Modules\Hr\Enums\ShiftSwapStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrShift;
use Modules\Hr\Models\HrShiftAssignment;
use Modules\Hr\Models\HrShiftBreak;
use Modules\Hr\Models\HrShiftReallocation;
use Modules\Hr\Models\HrShiftRotation;
use Modules\Hr\Models\HrShiftRotationAssignment;
use Modules\Hr\Models\HrShiftRotationStep;
use Modules\Hr\Models\HrShiftSwap;
use Modules\Hr\Services\HrAuthorizationService;
use Modules\Hr\Services\ShiftRosterService;

final class HrShiftController
{
    public function __construct(
        private readonly ShiftRosterService $rosterService,
        private readonly PermissionCheckerInterface $permissions,
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
            'code' => ['required', 'string', 'max:32', Rule::unique('hr_shifts', 'code')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:16'],
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s'],
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

        $halfDayThreshold = (int) ($validated['half_day_threshold_minutes'] ?? 240);
        $fullDayThreshold = (int) ($validated['full_day_threshold_minutes'] ?? 480);
        if ($halfDayThreshold > $fullDayThreshold) {
            throw ValidationException::withMessages([
                'half_day_threshold_minutes' => ['Half day threshold minutes must be less than or equal to full day threshold minutes.'],
            ]);
        }

        $autoDeductBreak = (bool) ($validated['auto_deduct_break'] ?? false);
        $autoDeductBreakMinutes = (int) ($validated['auto_deduct_break_minutes'] ?? 0);
        if ($autoDeductBreak && $autoDeductBreakMinutes <= 0) {
            throw ValidationException::withMessages([
                'auto_deduct_break_minutes' => ['Auto deduct break minutes must be greater than 0 when auto deduct break is enabled.'],
            ]);
        }

        $data = new ShiftData(
            code: $validated['code'],
            name: $validated['name'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            color: $validated['color'] ?? '#3B82F6',
            isOvernight: (bool) ($validated['is_overnight'] ?? false),
            graceInMinutes: (int) ($validated['grace_in_minutes'] ?? 15),
            graceOutMinutes: (int) ($validated['grace_out_minutes'] ?? 15),
            halfDayThresholdMinutes: $halfDayThreshold,
            fullDayThresholdMinutes: $fullDayThreshold,
            isBreakPaid: (bool) ($validated['is_break_paid'] ?? false),
            autoDeductBreak: $autoDeductBreak,
            autoDeductBreakMinutes: $autoDeductBreakMinutes,
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
            'expected_version' => ['required', 'integer', 'min:1'],
            'code' => ['sometimes', 'required', 'string', 'max:32', Rule::unique('hr_shifts', 'code')->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($shift)],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:16'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i,H:i:s'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i,H:i:s'],
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

        $expectedVersion = (int) $validated['expected_version'];

        $updated = DB::transaction(function () use ($request, $shift, $validated, $expectedVersion): ?HrShift {
            $shiftModel = HrShift::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $shift)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $shiftModel->row_version !== $expectedVersion) {
                return null;
            }

            $halfDayThreshold = $request->has('half_day_threshold_minutes')
                ? (int) $validated['half_day_threshold_minutes']
                : (int) $shiftModel->half_day_threshold_minutes;
            $fullDayThreshold = $request->has('full_day_threshold_minutes')
                ? (int) $validated['full_day_threshold_minutes']
                : (int) $shiftModel->full_day_threshold_minutes;

            if ($halfDayThreshold > $fullDayThreshold) {
                throw ValidationException::withMessages([
                    'half_day_threshold_minutes' => ['Half day threshold minutes must be less than or equal to full day threshold minutes.'],
                ]);
            }

            $autoDeductBreak = $request->has('auto_deduct_break')
                ? (bool) $validated['auto_deduct_break']
                : (bool) $shiftModel->auto_deduct_break;
            $autoDeductBreakMinutes = $request->has('auto_deduct_break_minutes')
                ? (int) $validated['auto_deduct_break_minutes']
                : (int) $shiftModel->auto_deduct_break_minutes;

            if ($autoDeductBreak && $autoDeductBreakMinutes <= 0) {
                throw ValidationException::withMessages([
                    'auto_deduct_break_minutes' => ['Auto deduct break minutes must be greater than 0 when auto deduct break is enabled.'],
                ]);
            }

            $data = new ShiftData(
                code: $request->has('code') ? $validated['code'] : $shiftModel->code,
                name: $request->has('name') ? $validated['name'] : $shiftModel->name,
                startTime: $request->has('start_time') ? $validated['start_time'] : (string) $shiftModel->start_time,
                endTime: $request->has('end_time') ? $validated['end_time'] : (string) $shiftModel->end_time,
                color: $request->has('color') ? ($validated['color'] ?? '#3B82F6') : $shiftModel->color,
                isOvernight: $request->has('is_overnight') ? (bool) $validated['is_overnight'] : (bool) $shiftModel->is_overnight,
                graceInMinutes: $request->has('grace_in_minutes') ? (int) $validated['grace_in_minutes'] : (int) $shiftModel->grace_in_minutes,
                graceOutMinutes: $request->has('grace_out_minutes') ? (int) $validated['grace_out_minutes'] : (int) $shiftModel->grace_out_minutes,
                halfDayThresholdMinutes: $halfDayThreshold,
                fullDayThresholdMinutes: $fullDayThreshold,
                isBreakPaid: $request->has('is_break_paid') ? (bool) $validated['is_break_paid'] : (bool) $shiftModel->is_break_paid,
                autoDeductBreak: $autoDeductBreak,
                autoDeductBreakMinutes: $autoDeductBreakMinutes,
                autoDeductThresholdMinutes: $request->has('auto_deduct_threshold_minutes') ? (int) $validated['auto_deduct_threshold_minutes'] : (int) $shiftModel->auto_deduct_threshold_minutes,
                isFlexible: $request->has('is_flexible') ? (bool) $validated['is_flexible'] : (bool) $shiftModel->is_flexible,
                status: $request->has('status') && isset($validated['status']) ? ShiftStatus::from($validated['status']) : $shiftModel->status,
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
                || HrShiftSwap::query()
                    ->where('tenant_id', $tenantId)
                    ->where(static fn ($q) => $q->where('requester_shift_id', $shift)->orWhere('target_shift_id', $shift))
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
            return response()->json(['message' => 'Cannot delete shift because it is referenced by assignments, swaps, rotations, reallocations, or attendance records.'], 422);
        }

        return response()->json(null, 204);
    }

    public function indexBreaks(TenantScopedRequest $request, int $shift): JsonResponse
    {
        $tenantId = $request->tenantId();
        HrShift::query()->where('tenant_id', $tenantId)->where('id', $shift)->firstOrFail();

        $breaks = HrShiftBreak::query()
            ->where('tenant_id', $tenantId)
            ->where('shift_id', $shift)
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $breaks]);
    }

    public function storeBreak(TenantScopedRequest $request, int $shift): JsonResponse
    {
        $tenantId = $request->tenantId();
        $shiftModel = HrShift::query()->where('tenant_id', $tenantId)->where('id', $shift)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'break_type' => ['nullable', 'string', 'max:32'],
            'start_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'is_paid' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);

        $break = HrShiftBreak::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $request->organizationUnitId() ?? $shiftModel->organization_unit_id,
            'shift_id' => $shiftModel->id,
            'name' => $validated['name'],
            'break_type' => $validated['break_type'] ?? 'meal',
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'is_paid' => (bool) ($validated['is_paid'] ?? false),
            'is_mandatory' => (bool) ($validated['is_mandatory'] ?? true),
        ]);

        return response()->json(['data' => $break], 201);
    }

    public function showBreak(TenantScopedRequest $request, int $shift, int $shiftBreak): JsonResponse
    {
        $break = HrShiftBreak::query()
            ->where('tenant_id', $request->tenantId())
            ->where('shift_id', $shift)
            ->where('id', $shiftBreak)
            ->firstOrFail();

        return response()->json(['data' => $break]);
    }

    public function updateBreak(TenantScopedRequest $request, int $shift, int $shiftBreak): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'break_type' => ['sometimes', 'required', 'string', 'max:32'],
            'start_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:480'],
            'is_paid' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
        ]);

        $expectedVersion = (int) $validated['expected_version'];

        $updated = DB::transaction(function () use ($request, $tenantId, $shift, $shiftBreak, $validated, $expectedVersion): ?HrShiftBreak {
            $breakModel = HrShiftBreak::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift)
                ->where('id', $shiftBreak)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $breakModel->row_version !== $expectedVersion) {
                return null;
            }

            $breakModel->update([
                'name' => $request->has('name') ? $validated['name'] : $breakModel->name,
                'break_type' => $request->has('break_type') ? $validated['break_type'] : $breakModel->break_type,
                'start_time' => $request->has('start_time') ? ($validated['start_time'] ?? null) : $breakModel->start_time,
                'end_time' => $request->has('end_time') ? ($validated['end_time'] ?? null) : $breakModel->end_time,
                'duration_minutes' => $request->has('duration_minutes') ? (int) $validated['duration_minutes'] : $breakModel->duration_minutes,
                'is_paid' => $request->has('is_paid') ? (bool) $validated['is_paid'] : $breakModel->is_paid,
                'is_mandatory' => $request->has('is_mandatory') ? (bool) $validated['is_mandatory'] : $breakModel->is_mandatory,
                'row_version' => ((int) $breakModel->row_version) + 1,
            ]);

            return $breakModel->fresh();
        });

        if ($updated === null) {
            return response()->json(['message' => 'Shift break was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroyBreak(TenantScopedRequest $request, int $shift, int $shiftBreak): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $result = DB::transaction(function () use ($request, $shift, $shiftBreak, $expectedVersion): string {
            $breakModel = HrShiftBreak::query()
                ->where('tenant_id', $request->tenantId())
                ->where('shift_id', $shift)
                ->where('id', $shiftBreak)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $breakModel->row_version !== $expectedVersion) {
                return 'conflict';
            }

            $breakModel->delete();

            return 'deleted';
        });

        if ($result === 'conflict') {
            return response()->json(['message' => 'Shift break was changed by someone else. Reload before deleting.'], 409);
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
            'day_of_week' => ['nullable', 'required_if:assignment_type,recurring', 'integer', 'between:1,7'],
            'start_date' => ['nullable', 'required_if:assignment_type,specific_date,date_range', 'date'],
            'end_date' => ['nullable', 'required_if:assignment_type,date_range', 'date', 'after_or_equal:start_date'],
            'is_custom_timing' => ['nullable', 'boolean'],
            'custom_start_time' => ['nullable', 'required_if:is_custom_timing,true,1', 'date_format:H:i,H:i:s'],
            'custom_end_time' => ['nullable', 'required_if:is_custom_timing,true,1', 'date_format:H:i,H:i:s'],
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

        try {
            $assignment = $this->rosterService->assignShift($request->tenantId(), $data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $assignment], 201);
    }

    public function showAssignment(TenantScopedRequest $request, int $assignment): JsonResponse
    {
        $assignmentModel = HrShiftAssignment::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $assignment)
            ->with(['employee', 'shift'])
            ->firstOrFail();

        return response()->json(['data' => $assignmentModel]);
    }

    public function updateAssignment(TenantScopedRequest $request, int $assignment): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'employee_id' => ['sometimes', 'required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['sometimes', 'required', 'integer', Rule::exists('hr_shifts', 'id')->where('tenant_id', $tenantId)],
            'assignment_type' => ['sometimes', 'required', 'string', Rule::enum(ShiftAssignmentType::class)],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_custom_timing' => ['nullable', 'boolean'],
            'custom_start_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'custom_end_time' => ['nullable', 'date_format:H:i,H:i:s'],
        ]);

        $expectedVersion = (int) $validated['expected_version'];

        try {
            $updated = DB::transaction(function () use ($request, $tenantId, $assignment, $validated, $expectedVersion): ?HrShiftAssignment {
                $assignmentModel = HrShiftAssignment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $assignment)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $assignmentModel->row_version !== $expectedVersion) {
                    return null;
                }

                if ($assignmentModel->is_cancelled) {
                    throw new InvalidArgumentException('Cannot update a cancelled shift assignment.');
                }

                $employeeId = $request->has('employee_id') ? (int) $validated['employee_id'] : (int) $assignmentModel->employee_id;
                $shiftId = $request->has('shift_id') ? (int) $validated['shift_id'] : (int) $assignmentModel->shift_id;
                $assignmentType = $request->has('assignment_type')
                    ? ShiftAssignmentType::from($validated['assignment_type'])
                    : $assignmentModel->assignment_type;
                $dayOfWeek = $request->has('day_of_week')
                    ? (isset($validated['day_of_week']) ? (int) $validated['day_of_week'] : null)
                    : $assignmentModel->day_of_week;
                $startDate = $request->has('start_date')
                    ? ($validated['start_date'] ?? null)
                    : ($assignmentModel->start_date !== null ? CarbonImmutable::parse($assignmentModel->start_date)->toDateString() : null);
                $endDate = $request->has('end_date')
                    ? ($validated['end_date'] ?? null)
                    : ($assignmentModel->end_date !== null ? CarbonImmutable::parse($assignmentModel->end_date)->toDateString() : null);
                $isCustomTiming = $request->has('is_custom_timing') ? (bool) $validated['is_custom_timing'] : (bool) $assignmentModel->is_custom_timing;
                $customStartTime = $request->has('custom_start_time') ? ($validated['custom_start_time'] ?? null) : $assignmentModel->custom_start_time;
                $customEndTime = $request->has('custom_end_time') ? ($validated['custom_end_time'] ?? null) : $assignmentModel->custom_end_time;

                if ($assignmentType === ShiftAssignmentType::Recurring && $dayOfWeek === null) {
                    throw ValidationException::withMessages(['day_of_week' => ['Day of week is required for recurring shift assignments.']]);
                }
                if (in_array($assignmentType, [ShiftAssignmentType::SpecificDate, ShiftAssignmentType::DateRange], true) && $startDate === null) {
                    throw ValidationException::withMessages(['start_date' => ['Start date is required for specific date and date range assignments.']]);
                }
                if ($assignmentType === ShiftAssignmentType::DateRange && $endDate === null) {
                    throw ValidationException::withMessages(['end_date' => ['End date is required for date range assignments.']]);
                }
                if ($startDate !== null && $endDate !== null && CarbonImmutable::parse($endDate)->lessThan(CarbonImmutable::parse($startDate))) {
                    throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
                }
                if ($isCustomTiming && ($customStartTime === null || $customEndTime === null)) {
                    throw ValidationException::withMessages(['custom_start_time' => ['Custom start and end times are required when custom timing is enabled.']]);
                }

                $employee = HrEmployee::query()->where('tenant_id', $tenantId)->where('id', $employeeId)->firstOrFail();
                if ($employee->status !== EmployeeStatus::Active) {
                    throw new InvalidArgumentException('Cannot assign shift to an inactive employee.');
                }

                $shift = HrShift::query()->where('tenant_id', $tenantId)->where('id', $shiftId)->firstOrFail();
                if ($shift->status !== ShiftStatus::Active) {
                    throw new InvalidArgumentException('Cannot assign an inactive shift.');
                }

                if ($assignmentType === ShiftAssignmentType::SpecificDate && $startDate !== null) {
                    $hasOverlap = HrShiftAssignment::query()
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $employeeId)
                        ->where('id', '!=', $assignmentModel->id)
                        ->where('assignment_type', ShiftAssignmentType::SpecificDate)
                        ->whereDate('start_date', $startDate)
                        ->where('is_cancelled', false)
                        ->exists();
                    if ($hasOverlap) {
                        throw new InvalidArgumentException(sprintf('Employee already has an active specific-date shift assignment on %s.', $startDate));
                    }

                    $hasLeave = HrLeaveRequest::query()
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $employeeId)
                        ->whereDate('start_date', '<=', $startDate)
                        ->whereDate('end_date', '>=', $startDate)
                        ->where('status', LeaveRequestStatus::Approved)
                        ->exists();
                    if ($hasLeave) {
                        throw new InvalidArgumentException(sprintf('Cannot assign shift: Employee has approved leave on %s.', $startDate));
                    }
                } elseif ($assignmentType === ShiftAssignmentType::DateRange && $startDate !== null && $endDate !== null) {
                    $hasOverlap = HrShiftAssignment::query()
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $employeeId)
                        ->where('id', '!=', $assignmentModel->id)
                        ->where('assignment_type', ShiftAssignmentType::DateRange)
                        ->whereDate('start_date', '<=', $endDate)
                        ->whereDate('end_date', '>=', $startDate)
                        ->where('is_cancelled', false)
                        ->exists();
                    if ($hasOverlap) {
                        throw new InvalidArgumentException('Employee already has an active overlapping date-range shift assignment.');
                    }

                    $hasLeave = HrLeaveRequest::query()
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $employeeId)
                        ->whereDate('start_date', '<=', $endDate)
                        ->whereDate('end_date', '>=', $startDate)
                        ->where('status', LeaveRequestStatus::Approved)
                        ->exists();
                    if ($hasLeave) {
                        throw new InvalidArgumentException('Cannot assign shift: Employee has approved leave within this date range.');
                    }
                } elseif ($assignmentType === ShiftAssignmentType::Recurring && $dayOfWeek !== null) {
                    $hasOverlap = HrShiftAssignment::query()
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $employeeId)
                        ->where('id', '!=', $assignmentModel->id)
                        ->where('assignment_type', ShiftAssignmentType::Recurring)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('is_cancelled', false)
                        ->exists();
                    if ($hasOverlap) {
                        throw new InvalidArgumentException(sprintf('Employee already has an active recurring shift assignment for day %d.', $dayOfWeek));
                    }
                }

                $assignmentModel->update([
                    'employee_id' => $employeeId,
                    'shift_id' => $shiftId,
                    'assignment_type' => $assignmentType,
                    'day_of_week' => $dayOfWeek,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_custom_timing' => $isCustomTiming,
                    'custom_start_time' => $customStartTime,
                    'custom_end_time' => $customEndTime,
                    'row_version' => ((int) $assignmentModel->row_version) + 1,
                ]);

                return $assignmentModel->fresh(['employee', 'shift']);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($updated === null) {
            return response()->json(['message' => 'Shift assignment was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function cancelAssignment(TenantScopedRequest $request, int $assignment): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        try {
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
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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
            ->with(['requesterEmployee', 'targetEmployee', 'requesterShift', 'targetShift'])
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

    public function showSwap(TenantScopedRequest $request, int $swap): JsonResponse
    {
        $swapModel = HrShiftSwap::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $swap)
            ->with(['requesterEmployee', 'targetEmployee', 'requesterShift', 'targetShift'])
            ->firstOrFail();

        return response()->json(['data' => $swapModel]);
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

        $requesterEmployeeId = (int) $validated['requester_employee_id'];
        $currentUserId = (int) $request->currentUserId();
        if (! $this->permissions->allows($currentUserId, $tenantId, HrAuthorizationService::MANAGE_SHIFTS)) {
            $ownsRequester = HrEmployee::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $requesterEmployeeId)
                ->where('user_id', $currentUserId)
                ->exists();

            if (! $ownsRequester) {
                return response()->json(['message' => 'You can only request shift swaps for your own employee record.'], 403);
            }
        }

        $data = new ShiftSwapData(
            requesterEmployeeId: $requesterEmployeeId,
            targetEmployeeId: (int) $validated['target_employee_id'],
            swapDate: $validated['swap_date'],
            targetSwapDate: $validated['target_swap_date'],
            reason: $validated['reason'],
            organizationUnitId: $request->organizationUnitId(),
        );

        try {
            $swap = $this->rosterService->requestSwap($tenantId, $data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $swap], 201);
    }

    public function peerActionSwap(TenantScopedRequest $request, int $swap): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'peer_employee_id' => ['required', 'integer'],
            'accept' => ['required', 'boolean'],
        ]);

        $peerEmployeeId = (int) $validated['peer_employee_id'];
        $currentUserId = (int) $request->currentUserId();
        if (! $this->permissions->allows($currentUserId, $tenantId, HrAuthorizationService::MANAGE_SHIFTS)) {
            $ownsPeer = HrEmployee::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $peerEmployeeId)
                ->where('user_id', $currentUserId)
                ->exists();

            if (! $ownsPeer) {
                return response()->json(['message' => 'You can only respond to shift swaps for your own employee record.'], 403);
            }
        }

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        try {
            $updated = DB::transaction(function () use ($tenantId, $swap, $peerEmployeeId, $validated, $expectedVersion): ?HrShiftSwap {
                $swapModel = HrShiftSwap::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $swap)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($expectedVersion !== null && (int) $swapModel->row_version !== $expectedVersion) {
                    return null;
                }

                return $this->rosterService->peerActionSwap($swapModel, $peerEmployeeId, (bool) $validated['accept']);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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

        try {
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
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($updated === null) {
            return response()->json(['message' => 'Shift swap was changed by someone else. Reload before acting.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function cancelSwap(TenantScopedRequest $request, int $swap): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);

        $expectedVersion = (int) $validated['expected_version'];
        $currentUserId = (int) $request->currentUserId();
        $canManage = $this->permissions->allows($currentUserId, $tenantId, HrAuthorizationService::MANAGE_SHIFTS);

        try {
            $result = DB::transaction(function () use ($tenantId, $swap, $expectedVersion, $currentUserId, $canManage): HrShiftSwap|string {
                $swapModel = HrShiftSwap::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $swap)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $canManage) {
                    $ownsRequester = HrEmployee::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', $swapModel->requester_employee_id)
                        ->where('user_id', $currentUserId)
                        ->exists();

                    if (! $ownsRequester) {
                        return 'forbidden';
                    }
                }

                if ((int) $swapModel->row_version !== $expectedVersion) {
                    return 'conflict';
                }

                return $this->rosterService->cancelSwap($swapModel);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($result === 'forbidden') {
            return response()->json(['message' => 'You can only cancel your own shift swap requests.'], 403);
        }

        if ($result === 'conflict') {
            return response()->json(['message' => 'Shift swap was changed by someone else. Reload before cancelling.'], 409);
        }

        return response()->json(['data' => $result]);
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

        try {
            $reallocation = $this->rosterService->reallocateCover($request->tenantId(), $data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $reallocation], 201);
    }

    public function showReallocation(TenantScopedRequest $request, int $reallocation): JsonResponse
    {
        $reallocationModel = HrShiftReallocation::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $reallocation)
            ->with(['absentEmployee', 'coverEmployee', 'shift'])
            ->firstOrFail();

        return response()->json(['data' => $reallocationModel]);
    }

    public function updateReallocation(TenantScopedRequest $request, int $reallocation): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'absent_employee_id' => ['sometimes', 'required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'cover_employee_id' => ['sometimes', 'required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['sometimes', 'required', 'integer', Rule::exists('hr_shifts', 'id')->where('tenant_id', $tenantId)],
            'reallocation_date' => ['sometimes', 'required', 'date'],
            'reason_code' => ['nullable', 'string', Rule::enum(ShiftReallocationReason::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $expectedVersion = (int) $validated['expected_version'];

        try {
            $updated = DB::transaction(function () use ($request, $tenantId, $reallocation, $validated, $expectedVersion): ?HrShiftReallocation {
                $reallocationModel = HrShiftReallocation::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $reallocation)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $reallocationModel->row_version !== $expectedVersion) {
                    return null;
                }

                $absentEmployeeId = $request->has('absent_employee_id') ? (int) $validated['absent_employee_id'] : (int) $reallocationModel->absent_employee_id;
                $coverEmployeeId = $request->has('cover_employee_id') ? (int) $validated['cover_employee_id'] : (int) $reallocationModel->cover_employee_id;
                $shiftId = $request->has('shift_id') ? (int) $validated['shift_id'] : (int) $reallocationModel->shift_id;
                $reallocationDate = $request->has('reallocation_date')
                    ? $validated['reallocation_date']
                    : CarbonImmutable::parse($reallocationModel->reallocation_date)->toDateString();

                if ($absentEmployeeId === $coverEmployeeId) {
                    throw new InvalidArgumentException('Absent employee and cover employee cannot be the same.');
                }

                $absentEmployee = HrEmployee::query()->where('tenant_id', $tenantId)->where('id', $absentEmployeeId)->firstOrFail();
                if ($absentEmployee->status !== EmployeeStatus::Active) {
                    throw new InvalidArgumentException('Absent employee must be active.');
                }

                $coverEmployee = HrEmployee::query()->where('tenant_id', $tenantId)->where('id', $coverEmployeeId)->firstOrFail();
                if ($coverEmployee->status !== EmployeeStatus::Active) {
                    throw new InvalidArgumentException('Cover employee must be active.');
                }

                $shift = HrShift::query()->where('tenant_id', $tenantId)->where('id', $shiftId)->firstOrFail();
                if ($shift->status !== ShiftStatus::Active) {
                    throw new InvalidArgumentException('Cannot reallocate an inactive shift.');
                }

                $coverOnLeave = HrLeaveRequest::query()
                    ->where('tenant_id', $tenantId)
                    ->where('employee_id', $coverEmployeeId)
                    ->whereDate('start_date', '<=', $reallocationDate)
                    ->whereDate('end_date', '>=', $reallocationDate)
                    ->where('status', LeaveRequestStatus::Approved)
                    ->exists();
                if ($coverOnLeave) {
                    throw new InvalidArgumentException('Cover employee has approved leave on the reallocation date.');
                }

                $duplicateAbsent = HrShiftReallocation::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', '!=', $reallocationModel->id)
                    ->where('absent_employee_id', $absentEmployeeId)
                    ->whereDate('reallocation_date', $reallocationDate)
                    ->exists();
                if ($duplicateAbsent) {
                    throw new InvalidArgumentException('Absent employee already has a shift reallocation on this date.');
                }

                $duplicateCover = HrShiftReallocation::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', '!=', $reallocationModel->id)
                    ->where('cover_employee_id', $coverEmployeeId)
                    ->whereDate('reallocation_date', $reallocationDate)
                    ->exists();
                if ($duplicateCover) {
                    throw new InvalidArgumentException('Cover employee already has a shift reallocation on this date.');
                }

                $reallocationModel->update([
                    'absent_employee_id' => $absentEmployeeId,
                    'cover_employee_id' => $coverEmployeeId,
                    'shift_id' => $shiftId,
                    'reallocation_date' => $reallocationDate,
                    'reason_code' => $request->has('reason_code') && isset($validated['reason_code'])
                        ? ShiftReallocationReason::from($validated['reason_code'])
                        : $reallocationModel->reason_code,
                    'notes' => $request->has('notes') ? ($validated['notes'] ?? null) : $reallocationModel->notes,
                    'row_version' => ((int) $reallocationModel->row_version) + 1,
                ]);

                return $reallocationModel->fresh(['absentEmployee', 'coverEmployee', 'shift']);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($updated === null) {
            return response()->json(['message' => 'Shift reallocation was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroyReallocation(TenantScopedRequest $request, int $reallocation): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $result = DB::transaction(function () use ($request, $reallocation, $expectedVersion): string {
            $reallocationModel = HrShiftReallocation::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $reallocation)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $reallocationModel->row_version !== $expectedVersion) {
                return 'conflict';
            }

            $reallocationModel->delete();

            return 'deleted';
        });

        if ($result === 'conflict') {
            return response()->json(['message' => 'Shift reallocation was changed by someone else. Reload before deleting.'], 409);
        }

        return response()->json(null, 204);
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

            $newCycleDays = $request->has('cycle_days') ? (int) $validated['cycle_days'] : (int) $rotationModel->cycle_days;

            $rotationModel->update([
                'code' => $request->has('code') ? $validated['code'] : $rotationModel->code,
                'name' => $request->has('name') ? $validated['name'] : $rotationModel->name,
                'description' => $request->has('description') ? ($validated['description'] ?? null) : $rotationModel->description,
                'cycle_days' => $newCycleDays,
                'status' => $request->has('status') ? $validated['status'] : $rotationModel->status,
                'row_version' => ((int) $rotationModel->row_version) + 1,
            ]);

            HrShiftRotationStep::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_rotation_id', $rotationModel->id)
                ->where('day_sequence', '>', $newCycleDays)
                ->delete();

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

    public function updateRotationStep(TenantScopedRequest $request, int $rotation, int $step): JsonResponse
    {
        $tenantId = $request->tenantId();
        $rotationModel = HrShiftRotation::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rotation)
            ->firstOrFail();

        $validated = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'day_sequence' => [
                'sometimes',
                'required',
                'integer',
                'between:1,'.$rotationModel->cycle_days,
                Rule::unique('hr_shift_rotation_steps', 'day_sequence')
                    ->where('tenant_id', $tenantId)
                    ->where('shift_rotation_id', $rotationModel->id)
                    ->ignore($step),
            ],
            'shift_id' => ['nullable', 'integer', Rule::exists('hr_shifts', 'id')->where('tenant_id', $tenantId)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $expectedVersion = (int) $validated['expected_version'];

        $updated = DB::transaction(function () use ($request, $tenantId, $rotation, $step, $validated, $expectedVersion): ?HrShiftRotationStep {
            $stepModel = HrShiftRotationStep::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_rotation_id', $rotation)
                ->where('id', $step)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $stepModel->row_version !== $expectedVersion) {
                return null;
            }

            $stepModel->update([
                'day_sequence' => $request->has('day_sequence') ? (int) $validated['day_sequence'] : $stepModel->day_sequence,
                'shift_id' => $request->has('shift_id') ? ($validated['shift_id'] !== null ? (int) $validated['shift_id'] : null) : $stepModel->shift_id,
                'notes' => $request->has('notes') ? ($validated['notes'] ?? null) : $stepModel->notes,
                'row_version' => ((int) $stepModel->row_version) + 1,
            ]);

            return $stepModel->fresh('shift');
        });

        if ($updated === null) {
            return response()->json(['message' => 'Rotation step was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
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

    public function showRotationAssignment(TenantScopedRequest $request, int $assignment): JsonResponse
    {
        $assignmentModel = HrShiftRotationAssignment::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $assignment)
            ->with(['employee', 'rotation'])
            ->firstOrFail();

        return response()->json(['data' => $assignmentModel]);
    }

    public function updateRotationAssignment(TenantScopedRequest $request, int $assignment): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'shift_rotation_id' => ['sometimes', 'required', 'integer', Rule::exists('hr_shift_rotations', 'id')->where('tenant_id', $tenantId)],
            'anchor_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $expectedVersion = (int) $validated['expected_version'];

        try {
            $updated = DB::transaction(function () use ($request, $tenantId, $assignment, $validated, $expectedVersion): ?HrShiftRotationAssignment {
                $assignmentModel = HrShiftRotationAssignment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $assignment)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $assignmentModel->row_version !== $expectedVersion) {
                    return null;
                }

                $rotationId = $request->has('shift_rotation_id') ? (int) $validated['shift_rotation_id'] : (int) $assignmentModel->shift_rotation_id;
                $anchorDate = $request->has('anchor_date')
                    ? $validated['anchor_date']
                    : CarbonImmutable::parse($assignmentModel->anchor_date)->toDateString();
                $endDate = $request->has('end_date')
                    ? ($validated['end_date'] ?? null)
                    : ($assignmentModel->end_date !== null ? CarbonImmutable::parse($assignmentModel->end_date)->toDateString() : null);
                $isActive = $request->has('is_active') ? (bool) $validated['is_active'] : (bool) $assignmentModel->is_active;

                if ($endDate !== null && CarbonImmutable::parse($endDate)->lessThan(CarbonImmutable::parse($anchorDate))) {
                    throw new InvalidArgumentException('Rotation assignment end date cannot be before anchor date.');
                }

                if ($isActive) {
                    $employee = HrEmployee::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', $assignmentModel->employee_id)
                        ->firstOrFail();
                    if ($employee->status !== EmployeeStatus::Active) {
                        throw new InvalidArgumentException('Cannot activate rotation assignment for an inactive employee.');
                    }

                    $rotation = HrShiftRotation::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', $rotationId)
                        ->firstOrFail();
                    if ($rotation->status !== 'active') {
                        throw new InvalidArgumentException('Cannot assign an inactive shift rotation.');
                    }

                    $hasOverlap = HrShiftRotationAssignment::query()
                        ->where('tenant_id', $tenantId)
                        ->where('employee_id', $assignmentModel->employee_id)
                        ->where('id', '!=', $assignmentModel->id)
                        ->where('is_active', true)
                        ->when($endDate !== null, static fn ($q) => $q->whereDate('anchor_date', '<=', $endDate))
                        ->where(static function ($q) use ($anchorDate): void {
                            $q->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $anchorDate);
                        })
                        ->exists();

                    if ($hasOverlap) {
                        throw new InvalidArgumentException('Employee already has an active overlapping shift rotation assignment.');
                    }
                }

                $assignmentModel->update([
                    'shift_rotation_id' => $rotationId,
                    'anchor_date' => $anchorDate,
                    'end_date' => $endDate,
                    'is_active' => $isActive,
                    'row_version' => ((int) $assignmentModel->row_version) + 1,
                ]);

                return $assignmentModel->fresh(['employee', 'rotation']);
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($updated === null) {
            return response()->json(['message' => 'Rotation assignment was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
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
