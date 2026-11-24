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
use Modules\Payroll\Enums\CalculationType;
use Modules\Payroll\Enums\SalaryComponentType;
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollSalaryComponent;

final class PayrollComponentController
{
    public function index(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $query = PayrollSalaryComponent::query()->where('tenant_id', $tenantId);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search')) {
            $search = (string) $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }

        return response()->json([
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:earning,deduction,statutory,employer_contribution'],
            'calculation_type' => ['required', 'string', 'in:fixed,percentage,hourly_rate,formula'],
            'is_taxable' => ['boolean'],
            'is_statutory_base' => ['boolean'],
            'is_active' => ['boolean'],
            'gl_account_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $component = PayrollSalaryComponent::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'type' => SalaryComponentType::from($validated['type']),
            'calculation_type' => CalculationType::from($validated['calculation_type']),
            'is_taxable' => $validated['is_taxable'] ?? true,
            'is_statutory_base' => $validated['is_statutory_base'] ?? true,
            'is_active' => $validated['is_active'] ?? true,
            'gl_account_name' => $validated['gl_account_name'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(['data' => $component], 201);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $component = PayrollSalaryComponent::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        return response()->json(['data' => $component]);
    }

    public function update(PayrollRequest $request, int $id): JsonResponse
    {
        $component = PayrollSalaryComponent::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'in:earning,deduction,statutory,employer_contribution'],
            'calculation_type' => ['sometimes', 'string', 'in:fixed,percentage,hourly_rate,formula'],
            'is_taxable' => ['sometimes', 'boolean'],
            'is_statutory_base' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'gl_account_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        if (isset($validated['type'])) {
            $validated['type'] = SalaryComponentType::from($validated['type']);
        }
        if (isset($validated['calculation_type'])) {
            $validated['calculation_type'] = CalculationType::from($validated['calculation_type']);
        }

        $component->update($validated);

        return response()->json(['data' => $component->fresh()]);
    }

    public function destroy(PayrollRequest $request, int $id): JsonResponse
    {
        $component = PayrollSalaryComponent::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        $component->delete();

        return response()->json(['message' => 'Component deleted successfully.']);
    }
}
