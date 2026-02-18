<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StorePayrollRequest;
use Modules\HR\Http\Requests\UpdatePayrollRequest;
use Modules\HR\Http\Resources\PayrollResource;
use Modules\HR\Services\PayrollService;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if ($month && $year) {
            $payrolls = $this->payrollService->getByMonthYear((int) $month, (int) $year);
        } else {
            $payrolls = collect();
        }

        return PayrollResource::collection($payrolls)->response();
    }

    public function store(StorePayrollRequest $request): JsonResponse
    {
        $payroll = $this->payrollService->create($request->validated());

        return (new PayrollResource($payroll))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $payroll = $this->payrollService->generatePayslip($id);
        if (! $payroll) {
            return response()->json(['message' => 'Payroll not found'], 404);
        }

        return (new PayrollResource($payroll))->response();
    }

    public function update(UpdatePayrollRequest $request, int $id): JsonResponse
    {
        $payroll = $this->payrollService->update($id, $request->validated());

        return (new PayrollResource($payroll))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->payrollService->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Payroll not found'], 404);
        }

        return response()->json(['message' => 'Payroll deleted successfully'], 200);
    }

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'allowance_details' => 'nullable|array',
            'deduction_details' => 'nullable|array',
        ]);

        $payroll = $this->payrollService->calculatePayroll(
            $request->input('employee_id'),
            $request->input('month'),
            $request->input('year'),
            $request->only(['allowances', 'deductions', 'allowance_details', 'deduction_details'])
        );

        return (new PayrollResource($payroll))->response();
    }

    public function processPayment(Request $request, int $id): JsonResponse
    {
        $request->validate(['payment_method' => 'nullable|string']);
        $payroll = $this->payrollService->processPayment($id, $request->input('payment_method', 'bank_transfer'));

        return (new PayrollResource($payroll))->response();
    }

    public function generatePayslip(int $id): JsonResponse
    {
        $payroll = $this->payrollService->generatePayslip($id);

        return (new PayrollResource($payroll))->response();
    }

    public function getByEmployee(int $employeeId): JsonResponse
    {
        $payrolls = $this->payrollService->getByEmployee($employeeId);

        return PayrollResource::collection($payrolls)->response();
    }
}
