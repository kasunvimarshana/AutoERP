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
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollStatutoryRule;

final class PayrollStatutoryRuleController
{
    public function index(PayrollRequest $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        $rules = PayrollStatutoryRule::query()
            ->where('tenant_id', $request->tenantId())
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($rules);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'employee_rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'employer_rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'applies_to_component_types' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $rule = PayrollStatutoryRule::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'employee_rate_percentage' => $validated['employee_rate_percentage'],
            'employer_rate_percentage' => $validated['employer_rate_percentage'],
            'applies_to_component_types' => $validated['applies_to_component_types'] ?? 'basic',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $rule], 201);
    }

    public function update(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:100'],
            'employee_rate_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'employer_rate_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'applies_to_component_types' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;
        unset($validated['expected_version']);

        $updated = DB::transaction(function () use ($request, $id, $expectedVersion, $validated): ?PayrollStatutoryRule {
            $rule = PayrollStatutoryRule::query()
                ->where('tenant_id', $request->tenantId())
                ->lockForUpdate()
                ->findOrFail($id);

            if ($expectedVersion !== null && (int) $rule->row_version !== $expectedVersion) {
                return null;
            }

            $validated['row_version'] = ((int) $rule->row_version) + 1;
            $rule->update($validated);

            return $rule->fresh();
        });

        if ($updated === null) {
            return response()->json(['message' => 'Statutory rule was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
    }
}
