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
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\CompOffClaimData;
use Modules\Hr\DTOs\LeavePolicyData;
use Modules\Hr\DTOs\LeaveRequestData;
use Modules\Hr\DTOs\LeaveTypeData;
use Modules\Hr\Enums\LeaveAccrualFrequency;
use Modules\Hr\Enums\LeaveDurationType;
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
        $types = HrLeaveType::query()
            ->where('tenant_id', $request->tenantId())
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $types]);
    }

    public function storeLeaveType(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:16'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_attachment' => ['nullable', 'boolean'],
            'attachment_mandatory_after_days' => ['nullable', 'integer', 'min:1'],
            'allow_half_day' => ['nullable', 'boolean'],
            'allow_hourly' => ['nullable', 'boolean'],
            'is_compensatory' => ['nullable', 'boolean'],
            'sandwich_rule_applied' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string'],
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

        $type = $this->leaveService->createLeaveType($request->tenantId(), $data);

        return response()->json(['data' => $type], 201);
    }

    public function leavePolicies(TenantScopedRequest $request): JsonResponse
    {
        $policies = HrLeavePolicy::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['leaveType', 'department', 'employmentType'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $policies]);
    }

    public function storeLeavePolicy(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'annual_allocation_days' => ['required', 'numeric'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date'],
            'accrual_frequency' => ['nullable', 'string'],
            'max_carry_forward_days' => ['nullable', 'numeric'],
            'carry_forward_expiry_months' => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'probation_restricted' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'integer'],
            'employment_type_id' => ['nullable', 'integer'],
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

        $policy = $this->leaveService->createLeavePolicy($request->tenantId(), $data);

        return response()->json(['data' => $policy], 201);
    }

    public function balances(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'calendar_year' => ['nullable', 'integer'],
        ]);

        $year = $validated['calendar_year'] ?? now()->year;

        $query = HrLeaveBalance::query()
            ->where('tenant_id', $request->tenantId())
            ->where('calendar_year', $year)
            ->with(['employee', 'leaveType']);

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function requests(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'leave_type_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrLeaveRequest::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['employee', 'leaveType', 'approver'])
            ->orderBy('created_at', 'desc');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['leave_type_id'])) {
            $query->where('leave_type_id', $validated['leave_type_id']);
        }

        return response()->json($query->paginate($validated['per_page'] ?? 25));
    }

    public function apply(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'leave_type_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'total_days' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['required', 'string'],
            'duration_type' => ['nullable', 'string'],
            'attachment_document_id' => ['nullable', 'integer'],
        ]);

        $data = new LeaveRequestData(
            employeeId: (int) $validated['employee_id'],
            leaveTypeId: (int) $validated['leave_type_id'],
            startDate: $validated['start_date'],
            endDate: $validated['end_date'],
            totalDays: (string) $validated['total_days'],
            reason: $validated['reason'],
            durationType: isset($validated['duration_type']) ? LeaveDurationType::from($validated['duration_type']) : LeaveDurationType::FullDay,
            attachmentDocumentId: isset($validated['attachment_document_id']) ? (int) $validated['attachment_document_id'] : null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $leaveRequest = $this->leaveService->applyLeave($request->tenantId(), $data);

        return response()->json(['data' => $leaveRequest], 201);
    }

    public function action(TenantScopedRequest $request, int $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'approve' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $model = HrLeaveRequest::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $leaveRequest)
            ->firstOrFail();

        $actioned = $this->leaveService->actionLeaveRequest(
            $model,
            (int) $request->currentUserId(),
            (bool) $validated['approve'],
            $validated['notes'] ?? null,
        );

        return response()->json(['data' => $actioned]);
    }

    public function cancel(TenantScopedRequest $request, int $leaveRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $model = HrLeaveRequest::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $leaveRequest)
            ->firstOrFail();

        $cancelled = $this->leaveService->cancelLeaveRequest(
            $model,
            (int) $request->currentUserId(),
            $validated['reason'],
        );

        return response()->json(['data' => $cancelled]);
    }

    public function claimCompOff(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'worked_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
            'claimed_days' => ['nullable', 'numeric'],
            'attendance_record_id' => ['nullable', 'integer'],
        ]);

        $data = new CompOffClaimData(
            employeeId: (int) $validated['employee_id'],
            workedDate: $validated['worked_date'],
            reason: $validated['reason'],
            claimedDays: (string) ($validated['claimed_days'] ?? '1.00'),
            attendanceRecordId: isset($validated['attendance_record_id']) ? (int) $validated['attendance_record_id'] : null,
            organizationUnitId: $request->organizationUnitId(),
        );

        $claim = $this->leaveService->claimCompOff($request->tenantId(), $data);

        return response()->json(['data' => $claim], 201);
    }

    public function actionCompOffClaim(TenantScopedRequest $request, int $claim): JsonResponse
    {
        $validated = $request->validate([
            'approve' => ['required', 'boolean'],
        ]);

        $model = HrCompOffClaim::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $claim)
            ->firstOrFail();

        $actioned = $this->leaveService->actionCompOffClaim(
            $model,
            (int) $request->currentUserId(),
            (bool) $validated['approve'],
        );

        return response()->json(['data' => $actioned]);
    }
}
