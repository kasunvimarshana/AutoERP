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

use Illuminate\Http\JsonResponse;
use Modules\Payroll\Enums\PayrollPaymentMethod;
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollEmployeeSalaryAssignment;

final class PayrollAssignmentController
{
    public function index(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $query = PayrollEmployeeSalaryAssignment::query()
            ->with(['employee', 'structure'])
            ->where('tenant_id', $tenantId);

        if ($request->has('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->has('structure_id')) {
            $query->where('salary_structure_id', (int) $request->input('structure_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json(['data' => $query->latest('effective_from')->get()]);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'salary_structure_id' => ['required', 'integer'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:bank_transfer,cash,cheque'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['boolean'],
        ]);

        $assignment = PayrollEmployeeSalaryAssignment::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
            'employee_id' => $validated['employee_id'],
            'salary_structure_id' => $validated['salary_structure_id'],
            'base_salary' => $validated['base_salary'],
            'hourly_rate' => $validated['hourly_rate'] ?? 0,
            'payment_method' => isset($validated['payment_method'])
                ? PayrollPaymentMethod::from($validated['payment_method'])
                : PayrollPaymentMethod::BankTransfer,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $assignment->fresh(['employee', 'structure'])], 201);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $assignment = PayrollEmployeeSalaryAssignment::query()
            ->with(['employee', 'structure.items.component'])
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        return response()->json(['data' => $assignment]);
    }

    public function update(PayrollRequest $request, int $id): JsonResponse
    {
        $assignment = PayrollEmployeeSalaryAssignment::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        $validated = $request->validate([
            'salary_structure_id' => ['sometimes', 'integer'],
            'base_salary' => ['sometimes', 'numeric', 'min:0'],
            'hourly_rate' => ['sometimes', 'numeric', 'min:0'],
            'payment_method' => ['sometimes', 'string', 'in:bank_transfer,cash,cheque'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['payment_method'])) {
            $validated['payment_method'] = PayrollPaymentMethod::from($validated['payment_method']);
        }

        $assignment->update($validated);

        return response()->json(['data' => $assignment->fresh(['employee', 'structure'])]);
    }
}
