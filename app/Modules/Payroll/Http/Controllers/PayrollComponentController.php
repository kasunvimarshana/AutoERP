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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        return response()->json($query->orderBy('name')->paginate($perPage));
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', Rule::in(SalaryComponentType::values())],
            'calculation_type' => ['required', 'string', Rule::in(CalculationType::values())],
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
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', Rule::in(SalaryComponentType::values())],
            'calculation_type' => ['sometimes', 'string', Rule::in(CalculationType::values())],
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

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;
        unset($validated['expected_version']);

        $updated = DB::transaction(function () use ($request, $id, $expectedVersion, $validated): ?PayrollSalaryComponent {
            $component = PayrollSalaryComponent::query()
                ->where('tenant_id', $request->tenantId())
                ->lockForUpdate()
                ->findOrFail($id);

            if ($expectedVersion !== null && (int) $component->row_version !== $expectedVersion) {
                return null;
            }

            $validated['row_version'] = ((int) $component->row_version) + 1;
            $component->update($validated);

            return $component->fresh();
        });

        if ($updated === null) {
            return response()->json(['message' => 'Salary component was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
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
