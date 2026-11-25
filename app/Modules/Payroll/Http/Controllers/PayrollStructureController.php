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
use Modules\Payroll\Enums\PayrollCycleType;
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollSalaryStructure;
use Modules\Payroll\Models\PayrollSalaryStructureItem;

final class PayrollStructureController
{
    public function index(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        $structures = PayrollSalaryStructure::query()
            ->with(['items.component'])
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($structures);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'cycle_type' => ['required', 'string', Rule::in(PayrollCycleType::values())],
            'currency_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.salary_component_id' => ['required_with:items', 'integer'],
            'items.*.calculation_type' => [
                'required_with:items',
                'string',
                Rule::in([
                    CalculationType::Fixed->value,
                    CalculationType::Percentage->value,
                    CalculationType::Formula->value,
                ]),
            ],
            'items.*.amount_or_rate' => ['required_with:items', 'numeric'],
            'items.*.percentage_basis_component_id' => ['nullable', 'integer'],
            'items.*.formula_expression' => ['nullable', 'string'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        $structure = DB::transaction(function () use ($validated, $request): PayrollSalaryStructure {
            $structure = PayrollSalaryStructure::create([
                'tenant_id' => $request->tenantId(),
                'organization_unit_id' => $request->organizationUnitId(),
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'cycle_type' => PayrollCycleType::from($validated['cycle_type']),
                'currency_id' => $validated['currency_id'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    PayrollSalaryStructureItem::create([
                        'tenant_id' => $request->tenantId(),
                        'organization_unit_id' => $request->organizationUnitId(),
                        'salary_structure_id' => $structure->id,
                        'salary_component_id' => $itemData['salary_component_id'],
                        'calculation_type' => CalculationType::from($itemData['calculation_type']),
                        'amount_or_rate' => $itemData['amount_or_rate'],
                        'percentage_basis_component_id' => $itemData['percentage_basis_component_id'] ?? null,
                        'formula_expression' => $itemData['formula_expression'] ?? null,
                        'sort_order' => $itemData['sort_order'] ?? 0,
                    ]);
                }
            }

            return $structure->fresh(['items.component']);
        });

        return response()->json(['data' => $structure], 201);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $structure = PayrollSalaryStructure::query()
            ->with(['items.component'])
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        return response()->json(['data' => $structure]);
    }

    public function update(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:100'],
            'cycle_type' => ['sometimes', 'string', Rule::in(PayrollCycleType::values())],
            'currency_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'items' => ['sometimes', 'array'],
            'items.*.salary_component_id' => ['required_with:items', 'integer'],
            'items.*.calculation_type' => [
                'required_with:items',
                'string',
                Rule::in([
                    CalculationType::Fixed->value,
                    CalculationType::Percentage->value,
                    CalculationType::Formula->value,
                ]),
            ],
            'items.*.amount_or_rate' => ['required_with:items', 'numeric'],
            'items.*.percentage_basis_component_id' => ['nullable', 'integer'],
            'items.*.formula_expression' => ['nullable', 'string'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $structure = DB::transaction(function () use ($id, $validated, $request, $expectedVersion): ?PayrollSalaryStructure {
            $structure = PayrollSalaryStructure::query()
                ->where('tenant_id', $request->tenantId())
                ->lockForUpdate()
                ->findOrFail($id);

            if ($expectedVersion !== null && (int) $structure->row_version !== $expectedVersion) {
                return null;
            }

            if (isset($validated['cycle_type'])) {
                $validated['cycle_type'] = PayrollCycleType::from($validated['cycle_type']);
            }

            $structure->update([
                'row_version' => ((int) $structure->row_version) + 1,
                'name' => $validated['name'] ?? $structure->name,
                'cycle_type' => $validated['cycle_type'] ?? $structure->cycle_type,
                'currency_id' => array_key_exists('currency_id', $validated) ? $validated['currency_id'] : $structure->currency_id,
                'description' => array_key_exists('description', $validated) ? $validated['description'] : $structure->description,
                'is_active' => $validated['is_active'] ?? $structure->is_active,
            ]);

            if (isset($validated['items'])) {
                $structure->items()->delete();
                foreach ($validated['items'] as $itemData) {
                    PayrollSalaryStructureItem::create([
                        'tenant_id' => $request->tenantId(),
                        'organization_unit_id' => $request->organizationUnitId(),
                        'salary_structure_id' => $structure->id,
                        'salary_component_id' => $itemData['salary_component_id'],
                        'calculation_type' => CalculationType::from($itemData['calculation_type']),
                        'amount_or_rate' => $itemData['amount_or_rate'],
                        'percentage_basis_component_id' => $itemData['percentage_basis_component_id'] ?? null,
                        'formula_expression' => $itemData['formula_expression'] ?? null,
                        'sort_order' => $itemData['sort_order'] ?? 0,
                    ]);
                }
            }

            return $structure->fresh(['items.component']);
        });

        if ($structure === null) {
            return response()->json(['message' => 'Salary structure was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $structure]);
    }

    public function destroy(PayrollRequest $request, int $id): JsonResponse
    {
        $structure = PayrollSalaryStructure::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        $structure->delete();

        return response()->json(['message' => 'Structure deleted successfully.']);
    }
}
