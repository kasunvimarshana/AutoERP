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
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\CompOffClaimData;
use Modules\Hr\DTOs\LeavePolicyData;
use Modules\Hr\DTOs\LeaveRequestData;
use Modules\Hr\DTOs\LeaveTypeData;
use Modules\Hr\Enums\CompOffStatus;
use Modules\Hr\Enums\LeaveAccrualFrequency;
use Modules\Hr\Enums\LeaveDurationType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\HrCompOffClaim;
use Modules\Hr\Models\HrLeaveBalance;
use Modules\Hr\Models\HrLeavePolicy;
use Modules\Hr\Models\HrLeaveRequest;
use Modules\Hr\Models\HrLeaveType;
use Modules\Hr\Services\LeaveManagementService;

final class HrLeaveController
{
    public function __construct(
        private readonly LeaveManagementService $leaveService,
    ) {}

    public function leaveTypes(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrLeaveType::query()
            ->where('tenant_id', $request->tenantId())
            ->orderBy('name');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function storeLeaveType(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('hr_leave_types', 'code')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:16'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_attachment' => ['nullable', 'boolean'],
            'attachment_mandatory_after_days' => ['nullable', 'integer', 'min:1'],
            'allow_half_day' => ['nullable', 'boolean'],
            'allow_hourly' => ['nullable', 'boolean'],
            'is_compensatory' => ['nullable', 'boolean'],
            'sandwich_rule_applied' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $data = new LeaveTypeData(
            code: $validated['code'],
            name: $validated['name'],
            color: $validated['color'] ?? '#10B981',
            isPaid: (bool) ($validated['is_paid'] ?? true),
            requiresAttachment: (bool) ($validated['requires_attachment'] ?? false),
            attachmentMandatoryAfterDays: (int) ($validated['attachment_mandatory_after_days'] ?? 2),
            allowHalfDay: (bool) ($validated['allow_half_day'] ?? true),
            allowHourly: (bool) ($validated['allow_hourly'] ?? false),
            isCompensatory: (bool) ($validated['is_compensatory'] ?? false),
            sandwichRuleApplied: (bool) ($validated['sandwich_rule_applied'] ?? false),
            status: $validated['status'] ?? 'active',
            organizationUnitId: $request->organizationUnitId(),
        );

        $type = $this->leaveService->createLeaveType($tenantId, $data);

        return response()->json(['data' => $type], 201);
    }

    public function showLeaveType(TenantScopedRequest $request, int $leaveType): JsonResponse
    {
        $model = HrLeaveType::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $leaveType)
            ->firstOrFail();

        return response()->json(['data' => $model]);
    }

    public function updateLeaveType(TenantScopedRequest $request, int $leaveType): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'code' => ['sometimes', 'required', 'string', 'max:32', Rule::unique('hr_leave_types', 'code')->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($leaveType)],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:16'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_attachment' => ['nullable', 'boolean'],
            'attachment_mandatory_after_days' => ['nullable', 'integer', 'min:1'],
            'allow_half_day' => ['nullable', 'boolean'],
            'allow_hourly' => ['nullable', 'boolean'],
            'is_compensatory' => ['nullable', 'boolean'],
            'sandwich_rule_applied' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $tenantId, $leaveType, $validated, $expectedVersion): ?HrLeaveType {
            $model = HrLeaveType::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $leaveType)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return null;
            }

            $data = new LeaveTypeData(
                code: $request->has('code') ? (string) $validated['code'] : (string) $model->code,
                name: $request->has('name') ? (string) $validated['name'] : (string) $model->name,
                color: $request->has('color') ? (string) ($validated['color'] ?? '#10B981') : (string) $model->color,
                isPaid: $request->has('is_paid') ? (bool) $validated['is_paid'] : (bool) $model->is_paid,
                requiresAttachment: $request->has('requires_attachment') ? (bool) $validated['requires_attachment'] : (bool) $model->requires_attachment,
                attachmentMandatoryAfterDays: $request->has('attachment_mandatory_after_days') ? (int) $validated['attachment_mandatory_after_days'] : (int) $model->attachment_mandatory_after_days,
                allowHalfDay: $request->has('allow_half_day') ? (bool) $validated['allow_half_day'] : (bool) $model->allow_half_day,
                allowHourly: $request->has('allow_hourly') ? (bool) $validated['allow_hourly'] : (bool) $model->allow_hourly,
                isCompensatory: $request->has('is_compensatory') ? (bool) $validated['is_compensatory'] : (bool) $model->is_compensatory,
                sandwichRuleApplied: $request->has('sandwich_rule_applied') ? (bool) $validated['sandwich_rule_applied'] : (bool) $model->sandwich_rule_applied,
                status: $request->has('status') ? (string) ($validated['status'] ?? 'active') : (string) $model->status,
                organizationUnitId: $request->organizationUnitId(),
            );

            return $this->leaveService->updateLeaveType($model, $data);
        });

        if ($updated === null) {
            return response()->json(['message' => 'Leave type was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroyLeaveType(TenantScopedRequest $request, int $leaveType): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $deleted = DB::transaction(function () use ($request, $leaveType, $expectedVersion): bool {
            $model = HrLeaveType::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $leaveType)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return false;
            }

            $this->leaveService->deleteLeaveType($model);

            return true;
        });

        if (! $deleted) {
            return response()->json(['message' => 'Leave type was changed by someone else. Reload before deleting.'], 409);
        }

        return response()->json(null, 204);
    }

    public function leavePolicies(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrLeavePolicy::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['leaveType', 'department', 'employmentType'])
            ->orderBy('name');

        if (! empty($validated['leave_type_id'])) {
            $query->where('leave_type_id', (int) $validated['leave_type_id']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function storeLeavePolicy(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'leave_type_id' => ['required', 'integer', Rule::exists('hr_leave_types', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:100'],
            'annual_allocation_days' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'accrual_frequency' => ['nullable', 'string', Rule::enum(LeaveAccrualFrequency::class)],
            'max_carry_forward_days' => ['nullable', 'numeric', 'min:0'],
            'carry_forward_expiry_months' => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'probation_restricted' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'integer', Rule::exists('hr_departments', 'id')->where('tenant_id', $tenantId)],
            'employment_type_id' => ['nullable', 'integer', Rule::exists('hr_employment_types', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data = new LeavePolicyData(
            leaveTypeId: (int) $validated['leave_type_id'],
            name: $validated['name'],
            annualAllocationDays: (string) $validated['annual_allocation_days'],
            effectiveFrom: $validated['effective_from'],
            accrualFrequency: isset($validated['accrual_frequency']) ? LeaveAccrualFrequency::from($validated['accrual_frequency']) : LeaveAccrualFrequency::AnnualUpfront,
            maxCarryForwardDays: (string) ($validated['max_carry_forward_days'] ?? '5.00'),
            carryForwardExpiryMonths: (int) ($validated['carry_forward_expiry_months'] ?? 3),
            maxConsecutiveDays: (int) ($validated['max_consecutive_days'] ?? 10),
            probationRestricted: (bool) ($validated['probation_restricted'] ?? false),
            effectiveTo: $validated['effective_to'] ?? null,
            departmentId: isset($validated['department_id']) ? (int) $validated['department_id'] : null,
            employmentTypeId: isset($validated['employment_type_id']) ? (int) $validated['employment_type_id'] : null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $policy = $this->leaveService->createLeavePolicy($tenantId, $data);

        return response()->json(['data' => $policy], 201);
    }

    public function showLeavePolicy(TenantScopedRequest $request, int $leavePolicy): JsonResponse
    {
        $model = HrLeavePolicy::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $leavePolicy)
            ->with(['leaveType', 'department', 'employmentType'])
            ->firstOrFail();

        return response()->json(['data' => $model]);
    }

    public function updateLeavePolicy(TenantScopedRequest $request, int $leavePolicy): JsonResponse
    {
        $tenantId = $request->tenantId();
        $existing = HrLeavePolicy::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $leavePolicy)
            ->firstOrFail();

        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'leave_type_id' => ['sometimes', 'required', 'integer', Rule::exists('hr_leave_types', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'annual_allocation_days' => ['sometimes', 'required', 'numeric', 'min:0'],
            'effective_from' => ['sometimes', 'required', 'date'],
            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:'.($request->input('effective_from') ?? $existing->effective_from->toDateString()),
            ],
            'accrual_frequency' => ['nullable', 'string', Rule::enum(LeaveAccrualFrequency::class)],
            'max_carry_forward_days' => ['nullable', 'numeric', 'min:0'],
            'carry_forward_expiry_months' => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'probation_restricted' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'integer', Rule::exists('hr_departments', 'id')->where('tenant_id', $tenantId)],
            'employment_type_id' => ['nullable', 'integer', Rule::exists('hr_employment_types', 'id')->where('tenant_id', $tenantId)],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = DB::transaction(function () use ($request, $tenantId, $leavePolicy, $validated, $expectedVersion): ?HrLeavePolicy {
            $model = HrLeavePolicy::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $leavePolicy)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return null;
            }

            $data = new LeavePolicyData(
                leaveTypeId: $request->has('leave_type_id') ? (int) $validated['leave_type_id'] : (int) $model->leave_type_id,
                name: $request->has('name') ? (string) $validated['name'] : (string) $model->name,
                annualAllocationDays: $request->has('annual_allocation_days') ? (string) $validated['annual_allocation_days'] : (string) $model->annual_allocation_days,
                effectiveFrom: $request->has('effective_from') ? (string) $validated['effective_from'] : $model->effective_from->toDateString(),
                accrualFrequency: $request->has('accrual_frequency') && isset($validated['accrual_frequency'])
                    ? LeaveAccrualFrequency::from($validated['accrual_frequency'])
                    : $model->accrual_frequency,
                maxCarryForwardDays: $request->has('max_carry_forward_days') ? (string) ($validated['max_carry_forward_days'] ?? '0.00') : (string) $model->max_carry_forward_days,
                carryForwardExpiryMonths: $request->has('carry_forward_expiry_months') ? (int) ($validated['carry_forward_expiry_months'] ?? 0) : (int) $model->carry_forward_expiry_months,
                maxConsecutiveDays: $request->has('max_consecutive_days') ? (int) ($validated['max_consecutive_days'] ?? 1) : (int) $model->max_consecutive_days,
                probationRestricted: $request->has('probation_restricted') ? (bool) $validated['probation_restricted'] : (bool) $model->probation_restricted,
                effectiveTo: $request->has('effective_to') ? ($validated['effective_to'] ?? null) : $model->effective_to?->toDateString(),
                departmentId: $request->has('department_id') ? (isset($validated['department_id']) ? (int) $validated['department_id'] : null) : $model->department_id,
                employmentTypeId: $request->has('employment_type_id') ? (isset($validated['employment_type_id']) ? (int) $validated['employment_type_id'] : null) : $model->employment_type_id,
                organizationUnitId: $request->organizationUnitId(),
            );

            return $this->leaveService->updateLeavePolicy($model, $data);
        });

        if ($updated === null) {
            return response()->json(['message' => 'Leave policy was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }

    public function destroyLeavePolicy(TenantScopedRequest $request, int $leavePolicy): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $deleted = DB::transaction(function () use ($request, $leavePolicy, $expectedVersion): bool {
            $model = HrLeavePolicy::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $leavePolicy)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return false;
            }

            $this->leaveService->deleteLeavePolicy($model);

            return true;
        });

        if (! $deleted) {
            return response()->json(['message' => 'Leave policy was changed by someone else. Reload before deleting.'], 409);
        }

        return response()->json(null, 204);
    }

    public function balances(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'calendar_year' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $year = $validated['calendar_year'] ?? (int) substr($request->workspaceToday(), 0, 4);

        $query = HrLeaveBalance::query()
            ->where('tenant_id', $request->tenantId())
            ->where('calendar_year', $year)
            ->with(['employee', 'leaveType']);

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', (int) $validated['employee_id']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function requests(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::enum(LeaveRequestStatus::class)],
            'leave_type_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrLeaveRequest::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['employee', 'leaveType', 'approver'])
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', (int) $validated['employee_id']);
        }
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['leave_type_id'])) {
            $query->where('leave_type_id', (int) $validated['leave_type_id']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function showLeaveRequest(TenantScopedRequest $request, int $leaveRequest): JsonResponse
    {
        $model = HrLeaveRequest::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $leaveRequest)
            ->with(['employee', 'leaveType', 'approver'])
            ->firstOrFail();

        return response()->json(['data' => $model]);
    }

    public function apply(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'leave_type_id' => ['required', 'integer', Rule::exists('hr_leave_types', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_days' => ['nullable', 'numeric', 'min:0.25'],
            'reason' => ['required', 'string'],
            'duration_type' => ['nullable', 'string', Rule::enum(LeaveDurationType::class)],
            'attachment_document_id' => ['nullable', 'integer', Rule::exists('hr_employee_documents', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data = new LeaveRequestData(
            employeeId: (int) $validated['employee_id'],
            leaveTypeId: (int) $validated['leave_type_id'],
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            totalDays: (string) ($validated['total_days'] ?? '0.00'),
            reason: $validated['reason'],
            durationType: isset($validated['duration_type']) ? LeaveDurationType::from($validated['duration_type']) : LeaveDurationType::FullDay,
            attachmentDocumentId: isset($validated['attachment_document_id']) ? (int) $validated['attachment_document_id'] : null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $leaveRequest = $this->leaveService->applyLeave($tenantId, $data);

        return response()->json(['data' => $leaveRequest], 201);
    }

    public function action(TenantScopedRequest $request, int $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'approve' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $actioned = DB::transaction(function () use ($request, $leaveRequest, $validated, $expectedVersion): ?HrLeaveRequest {
            $model = HrLeaveRequest::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $leaveRequest)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return null;
            }

            return $this->leaveService->actionLeaveRequest(
                $model,
                (int) $request->currentUserId(),
                (bool) $validated['approve'],
                $validated['notes'] ?? null,
            );
        });

        if ($actioned === null) {
            return response()->json(['message' => 'Leave request was changed by someone else. Reload before acting.'], 409);
        }

        return response()->json(['data' => $actioned]);
    }

    public function cancel(TenantScopedRequest $request, int $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'reason' => ['required', 'string'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $cancelled = DB::transaction(function () use ($request, $leaveRequest, $validated, $expectedVersion): ?HrLeaveRequest {
            $model = HrLeaveRequest::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $leaveRequest)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return null;
            }

            return $this->leaveService->cancelLeaveRequest(
                $model,
                (int) $request->currentUserId(),
                $validated['reason'],
            );
        });

        if ($cancelled === null) {
            return response()->json(['message' => 'Leave request was changed by someone else. Reload before cancelling.'], 409);
        }

        return response()->json(['data' => $cancelled]);
    }

    public function compOffClaims(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', Rule::enum(CompOffStatus::class)],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrCompOffClaim::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['employee'])
            ->orderByDesc('worked_date')
            ->orderByDesc('id');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', (int) $validated['employee_id']);
        }
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        return response()->json($query->paginate((int) ($validated['per_page'] ?? 25)));
    }

    public function claimCompOff(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('hr_employees', 'id')->where('tenant_id', $tenantId)],
            'worked_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
            'claimed_days' => ['nullable', 'numeric', 'min:0.5'],
            'attendance_record_id' => ['nullable', 'integer', Rule::exists('hr_attendance_records', 'id')->where('tenant_id', $tenantId)],
        ]);

        $data = new CompOffClaimData(
            employeeId: (int) $validated['employee_id'],
            workedDate: $validated['worked_date'],
            reason: $validated['reason'],
            claimedDays: (string) ($validated['claimed_days'] ?? '1.00'),
            attendanceRecordId: isset($validated['attendance_record_id']) ? (int) $validated['attendance_record_id'] : null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $claim = $this->leaveService->claimCompOff($tenantId, $data);

        return response()->json(['data' => $claim], 201);
    }

    public function actionCompOffClaim(TenantScopedRequest $request, int $claim): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'approve' => ['required', 'boolean'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $actioned = DB::transaction(function () use ($request, $claim, $validated, $expectedVersion): ?HrCompOffClaim {
            $model = HrCompOffClaim::query()
                ->where('tenant_id', $request->tenantId())
                ->where('id', $claim)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                return null;
            }

            return $this->leaveService->actionCompOffClaim(
                $model,
                (int) $request->currentUserId(),
                (bool) $validated['approve'],
            );
        });

        if ($actioned === null) {
            return response()->json(['message' => 'Comp-off claim was changed by someone else. Reload before acting.'], 409);
        }

        return response()->json(['data' => $actioned]);
    }
}
