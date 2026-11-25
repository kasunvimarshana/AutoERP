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
use Illuminate\Validation\Rule;
use Modules\Payroll\Enums\PayrollCycleType;
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollPayPeriod;

final class PayrollPeriodController
{
    public function index(PayrollRequest $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        $periods = PayrollPayPeriod::query()
            ->where('tenant_id', $request->tenantId())
            ->orderByDesc('period_start')
            ->paginate($perPage);

        return response()->json($periods);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'cycle_type' => ['required', 'string', Rule::in(PayrollCycleType::values())],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'payout_date' => ['required', 'date', 'after_or_equal:period_start'],
            'total_working_days' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        $period = PayrollPayPeriod::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
            'name' => $validated['name'],
            'cycle_type' => PayrollCycleType::from($validated['cycle_type']),
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'payout_date' => $validated['payout_date'],
            'total_working_days' => $validated['total_working_days'],
            'is_closed' => false,
        ]);

        return response()->json(['data' => $period], 201);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $period = PayrollPayPeriod::query()
            ->with(['payRuns'])
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        return response()->json(['data' => $period]);
    }
}
