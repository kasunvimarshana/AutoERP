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

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\AttendanceAdjustmentData;
use Modules\Hr\DTOs\AttendanceRegularizationData;
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\Enums\AttendanceStatus;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Enums\RegularizationStatus;
use Modules\Hr\Enums\RegularizationType;
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Models\HrAttendanceRegularization;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\AttendanceCalculationEngine;
use Modules\Hr\Services\AttendanceRegularizationService;
use Modules\Hr\Services\HrAuthorizationService;

final class HrAttendanceController
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
        private readonly AttendanceRegularizationService $regularizationService,
        private readonly PermissionCheckerInterface $permissions,
    ) {}

    public function punch(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'punch_time' => ['nullable', 'date'],
            'punch_source' => ['nullable', 'string', Rule::enum(PunchSource::class)],
            'device_id' => ['nullable', 'string', 'max:64'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $data = new PunchData(
            employeeId: (int) $validated['employee_id'],
            punchType: PunchType::from($validated['punch_type']),
            punchTime: $validated['punch_time'] ?? null,
            punchSource: isset($validated['punch_source']) ? PunchSource::from($validated['punch_source']) : PunchSource::Web,
            deviceId: $validated['device_id'] ?? null,
            ipAddress: $validated['ip_address'] ?? $request->ip(),
            latitude: isset($validated['latitude']) ? (string) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (string) $validated['longitude'] : null,
            notes: $validated['notes'] ?? null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $punch = $this->calculationEngine->recordPunch($request->tenantId(), $data);

        return response()->json(['data' => $punch], 201);
    }

    public function today(TenantScopedRequest $request, int $employee): JsonResponse
    {
        $today = $request->workspaceToday();

        $record = HrAttendanceRecord::query()
            ->where('tenant_id', $request->tenantId())
            ->where('employee_id', $employee)
            ->whereDate('attendance_date', $today)
            ->with(['shift', 'punches' => fn ($q) => $q->orderBy('punch_time', 'asc')])
            ->first();

        return response()->json([
            'data' => [
                'date' => $today,
                'record' => $record,
                'punches' => $record?->punches ?? [],
            ],
        ]);
    }

    public function records(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => array_values(array_filter([
                'nullable',
                'date',
                $request->filled('start_date') ? 'after_or_equal:start_date' : null,
            ])),
            'employee_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::enum(AttendanceStatus::class)],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrAttendanceRecord::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['employee.department', 'shift', 'punches'])
            ->orderBy('attendance_date', 'desc');

        if (! empty($validated['start_date'])) {
            $query->whereDate('attendance_date', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('attendance_date', '<=', $validated['end_date']);
        }
        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $validated['department_id']));
        }

        $records = $query->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json($records);
    }

    public function requestRegularization(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'attendance_date' => ['required', 'date'],
            'regularization_type' => ['required', 'string', Rule::enum(RegularizationType::class)],
            'proposed_clock_in' => ['nullable', 'date'],
            'proposed_clock_out' => ['nullable', 'date'],
            'reason_code' => ['required', 'string', 'max:32'],
            'employee_notes' => ['required', 'string'],
            'attendance_record_id' => ['nullable', 'integer'],
        ]);

        $data = new AttendanceRegularizationData(
            employeeId: (int) $validated['employee_id'],
            attendanceDate: $validated['attendance_date'],
            regularizationType: RegularizationType::from($validated['regularization_type']),
            employeeNotes: $validated['employee_notes'],
            proposedClockIn: $validated['proposed_clock_in'] ?? null,
            proposedClockOut: $validated['proposed_clock_out'] ?? null,
            reasonCode: $validated['reason_code'],
            attendanceRecordId: isset($validated['attendance_record_id']) ? (int) $validated['attendance_record_id'] : null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $regularization = $this->regularizationService->requestRegularization($request->tenantId(), $data);

        return response()->json(['data' => $regularization], 201);
    }

    public function listRegularizations(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::enum(RegularizationStatus::class)],
            'employee_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrAttendanceRegularization::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['employee', 'attendanceRecord', 'actioner'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function actionRegularization(TenantScopedRequest $request, int $regularization): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'approve' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        try {
            $actioned = DB::transaction(function () use ($request, $regularization, $validated, $expectedVersion): ?HrAttendanceRegularization {
                $regularizationModel = HrAttendanceRegularization::query()
                    ->where('tenant_id', $request->tenantId())
                    ->where('id', $regularization)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($expectedVersion !== null && (int) $regularizationModel->row_version !== $expectedVersion) {
                    return null;
                }

                return $this->regularizationService->actionRegularization(
                    $regularizationModel,
                    (int) $request->currentUserId(),
                    (bool) $validated['approve'],
                    $validated['notes'] ?? null,
                );
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($actioned === null) {
            return response()->json(['message' => 'Regularization request was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $actioned]);
    }

    public function cancelRegularization(TenantScopedRequest $request, int $regularization): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tenantId = $request->tenantId();
        $currentUserId = (int) $request->currentUserId();
        $expectedVersion = (int) $validated['expected_version'];
        $canManage = $this->permissions->allows($currentUserId, $tenantId, HrAuthorizationService::MANAGE_ATTENDANCE);

        try {
            $result = DB::transaction(function () use ($tenantId, $regularization, $expectedVersion, $currentUserId, $canManage, $validated): HrAttendanceRegularization|string {
                $regularizationModel = HrAttendanceRegularization::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $regularization)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $canManage) {
                    $ownsEmployee = HrEmployee::query()
                        ->where('tenant_id', $tenantId)
                        ->where('id', $regularizationModel->employee_id)
                        ->where('user_id', $currentUserId)
                        ->exists();

                    if (! $ownsEmployee) {
                        return 'forbidden';
                    }
                }

                if ((int) $regularizationModel->row_version !== $expectedVersion) {
                    return 'conflict';
                }

                return $this->regularizationService->cancelRegularization(
                    $regularizationModel,
                    $validated['notes'] ?? null,
                );
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($result === 'forbidden') {
            return response()->json(['message' => 'You can only cancel your own attendance regularization requests.'], 403);
        }

        if ($result === 'conflict') {
            return response()->json(['message' => 'Regularization request was changed by someone else. Reload before cancelling.'], 409);
        }

        return response()->json(['data' => $result]);
    }

    public function directAdjust(TenantScopedRequest $request, int $record): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'new_status' => ['required', 'string', Rule::enum(AttendanceStatus::class)],
            'new_worked_minutes' => ['required', 'integer', 'min:0'],
            'new_overtime_minutes' => ['required', 'integer', 'min:0'],
            'adjustment_reason' => ['required', 'string'],
            'regularization_id' => ['nullable', 'integer'],
        ]);

        $tenantId = $request->tenantId();
        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $adjustment = DB::transaction(function () use ($tenantId, $record, $validated, $request, $expectedVersion) {
            $recordModel = HrAttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $record)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $recordModel->row_version !== $expectedVersion) {
                return null;
            }

            $data = new AttendanceAdjustmentData(
                attendanceRecordId: $record,
                newStatus: AttendanceStatus::from($validated['new_status']),
                newWorkedMinutes: (int) $validated['new_worked_minutes'],
                newOvertimeMinutes: (int) $validated['new_overtime_minutes'],
                adjustmentReason: $validated['adjustment_reason'],
                adjustedBy: $request->currentUserId(),
                regularizationId: isset($validated['regularization_id']) ? (int) $validated['regularization_id'] : null,
            );

            return $this->regularizationService->directAdjust($tenantId, $data);
        });

        if ($adjustment === null) {
            return response()->json(['message' => 'Attendance record was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $adjustment], 201);
    }

    public function lock(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $count = $this->regularizationService->lockRecords($request->tenantId(), $validated['start_date'], $validated['end_date']);

        return response()->json(['data' => ['locked_count' => $count]]);
    }
}
