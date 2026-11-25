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
use Modules\Payroll\Models\PayrollTaxBracket;
use Modules\Payroll\Models\PayrollTaxTable;

final class PayrollTaxTableController
{
    public function index(PayrollRequest $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        $tables = PayrollTaxTable::query()
            ->with(['brackets'])
            ->where('tenant_id', $request->tenantId())
            ->orderByDesc('effective_year')
            ->paginate($perPage);

        return response()->json($tables);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:100'],
            'effective_year' => ['required', 'integer'],
            'standard_personal_relief' => ['nullable', 'numeric'],
            'is_active' => ['boolean'],
            'brackets' => ['required', 'array', 'min:1'],
            'brackets.*.bracket_order' => ['required', 'integer'],
            'brackets.*.threshold_from' => ['required', 'numeric'],
            'brackets.*.threshold_to' => ['nullable', 'numeric'],
            'brackets.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $table = DB::transaction(function () use ($validated, $request): PayrollTaxTable {
            $taxTable = PayrollTaxTable::create([
                'tenant_id' => $request->tenantId(),
                'organization_unit_id' => $request->organizationUnitId(),
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'effective_year' => $validated['effective_year'],
                'standard_personal_relief' => $validated['standard_personal_relief'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['brackets'] as $bracketData) {
                PayrollTaxBracket::create([
                    'tenant_id' => $request->tenantId(),
                    'organization_unit_id' => $request->organizationUnitId(),
                    'tax_table_id' => $taxTable->id,
                    'bracket_order' => $bracketData['bracket_order'],
                    'threshold_from' => $bracketData['threshold_from'],
                    'threshold_to' => $bracketData['threshold_to'] ?? null,
                    'rate_percentage' => $bracketData['rate_percentage'],
                ]);
            }

            return $taxTable->fresh(['brackets']);
        });

        return response()->json(['data' => $table], 201);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $taxTable = PayrollTaxTable::query()
            ->with(['brackets'])
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        return response()->json(['data' => $taxTable]);
    }

    public function update(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:100'],
            'effective_year' => ['sometimes', 'integer'],
            'standard_personal_relief' => ['nullable', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
            'brackets' => ['sometimes', 'array'],
            'brackets.*.bracket_order' => ['required_with:brackets', 'integer'],
            'brackets.*.threshold_from' => ['required_with:brackets', 'numeric'],
            'brackets.*.threshold_to' => ['nullable', 'numeric'],
            'brackets.*.rate_percentage' => ['required_with:brackets', 'numeric', 'min:0', 'max:100'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $taxTable = DB::transaction(function () use ($id, $validated, $request, $expectedVersion): ?PayrollTaxTable {
            $taxTable = PayrollTaxTable::query()
                ->where('tenant_id', $request->tenantId())
                ->lockForUpdate()
                ->findOrFail($id);

            if ($expectedVersion !== null && (int) $taxTable->row_version !== $expectedVersion) {
                return null;
            }

            $taxTable->update([
                'row_version' => ((int) $taxTable->row_version) + 1,
                'name' => $validated['name'] ?? $taxTable->name,
                'effective_year' => $validated['effective_year'] ?? $taxTable->effective_year,
                'standard_personal_relief' => array_key_exists('standard_personal_relief', $validated)
                    ? $validated['standard_personal_relief']
                    : $taxTable->standard_personal_relief,
                'is_active' => $validated['is_active'] ?? $taxTable->is_active,
            ]);

            if (isset($validated['brackets'])) {
                $taxTable->brackets()->delete();
                foreach ($validated['brackets'] as $bracketData) {
                    PayrollTaxBracket::create([
                        'tenant_id' => $request->tenantId(),
                        'organization_unit_id' => $request->organizationUnitId(),
                        'tax_table_id' => $taxTable->id,
                        'bracket_order' => $bracketData['bracket_order'],
                        'threshold_from' => $bracketData['threshold_from'],
                        'threshold_to' => $bracketData['threshold_to'] ?? null,
                        'rate_percentage' => $bracketData['rate_percentage'],
                    ]);
                }
            }

            return $taxTable->fresh(['brackets']);
        });

        if ($taxTable === null) {
            return response()->json(['message' => 'Tax table was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $taxTable]);
    }
}
