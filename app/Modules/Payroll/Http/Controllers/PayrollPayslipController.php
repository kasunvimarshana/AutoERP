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
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollPayslip;

final class PayrollPayslipController
{
    public function index(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $query = PayrollPayslip::query()
            ->with(['employee', 'payRun.period', 'structure'])
            ->where('tenant_id', $tenantId);

        if ($request->has('pay_run_id')) {
            $query->where('pay_run_id', (int) $request->input('pay_run_id'));
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $payslip = PayrollPayslip::query()
            ->with(['employee', 'payRun.period', 'structure', 'items.component'])
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        return response()->json(['data' => $payslip]);
    }
}
