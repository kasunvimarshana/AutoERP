<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Modules\Payroll\Enums\AdjustmentStatus;
use Modules\Payroll\Enums\AdjustmentType;
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollAdjustment;
use Modules\Payroll\Services\PayrollRetroactivePayService;

final class PayrollAdjustmentController
{
    public function calculateRetroArrears(PayrollRequest $request, PayrollRetroactivePayService $retroService): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'effective_from_date' => ['required', 'date'],
            'target_pay_period_id' => ['required', 'integer'],
        ]);

        $userId = $request->currentUserId();
        if ($userId === null) {
            throw new AuthenticationException('Authenticated user context is required.');
        }

        $adjustment = $retroService->calculateSalaryArrears(
            tenantId: $request->tenantId(),
            employeeId: (int) $validated['employee_id'],
            effectiveFromDate: $validated['effective_from_date'],
            targetPayPeriodId: (int) $validated['target_pay_period_id'],
            userId: $userId,
        );

        if ($adjustment === null) {
            return response()->json([
                'message' => 'No historical salary delta found for the specified effective date.',
                'data' => null,
            ]);
        }

        return response()->json([
            'message' => 'Retroactive salary arrears calculated and queued successfully.',
            'data' => $adjustment->fresh(['employee', 'payPeriod']),
        ], 201);
    }

    public function index(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $query = PayrollAdjustment::query()
            ->with(['employee', 'component', 'payPeriod', 'approvedByUser'])
            ->where('tenant_id', $tenantId);

        if ($request->has('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'adjustment_type' => ['required', 'string', 'in:addition,deduction,retro_pay,reimbursement,correction'],
            'salary_component_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'effective_pay_period_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $userId = $request->currentUserId();
        if ($userId === null) {
            throw new AuthenticationException('Authenticated user context is required.');
        }

        $adjustment = PayrollAdjustment::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
            'employee_id' => $validated['employee_id'],
            'adjustment_type' => AdjustmentType::from($validated['adjustment_type']),
            'salary_component_id' => $validated['salary_component_id'] ?? null,
            'amount' => $validated['amount'],
            'effective_pay_period_id' => $validated['effective_pay_period_id'] ?? null,
            'status' => AdjustmentStatus::Pending,
            'reason' => $validated['reason'],
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $adjustment->fresh(['employee', 'component', 'payPeriod'])], 201);
    }

    public function cancel(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $adjustment = PayrollAdjustment::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        if (isset($validated['expected_version']) && (int) $adjustment->row_version !== (int) $validated['expected_version']) {
            return response()->json(['message' => 'Payroll adjustment was changed by someone else. Reload before saving.'], 409);
        }

        if ($adjustment->status === AdjustmentStatus::Applied) {
            return response()->json(['message' => 'Cannot cancel an applied adjustment.'], 422);
        }

        $adjustment->update([
            'status' => AdjustmentStatus::Cancelled,
        ]);

        return response()->json(['data' => $adjustment->fresh()]);
    }
}
