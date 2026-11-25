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

use App\Http\Controllers\Controller;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Modules\Payroll\Enums\LoanType;
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollEmployeeLoan;
use Modules\Payroll\Services\PayrollLoanService;

final class PayrollLoanController extends Controller
{
    public function __construct(
        private readonly PayrollLoanService $loanService,
    ) {}

    public function index(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();

        $query = PayrollEmployeeLoan::query()
            ->with(['employee', 'deductionStartPeriod', 'repayments'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('loan_type')) {
            $query->where('loan_type', $request->input('loan_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json([
            'data' => $query->orderByDesc('id')->paginate((int) $request->input('per_page', 20)),
        ]);
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();

        $validated = $request->validate([
            'organization_unit_id' => ['nullable', 'integer'],
            'employee_id' => ['required', 'integer'],
            'loan_type' => ['required', 'string', 'in:'.implode(',', array_column(LoanType::cases(), 'value'))],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'annual_interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'term_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'deduction_start_period_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $loan = $this->loanService->applyLoan([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $validated['organization_unit_id'] ?? null,
            'employee_id' => (int) $validated['employee_id'],
            'loan_type' => $validated['loan_type'],
            'principal_amount' => $validated['principal_amount'],
            'annual_interest_rate' => $validated['annual_interest_rate'] ?? '0.00',
            'term_months' => (int) ($validated['term_months'] ?? 1),
            'deduction_start_period_id' => isset($validated['deduction_start_period_id']) ? (int) $validated['deduction_start_period_id'] : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Loan application created successfully.',
            'data' => $loan->load(['employee', 'deductionStartPeriod']),
        ], 201);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $tenantId = $request->tenantId();

        $loan = PayrollEmployeeLoan::query()
            ->with(['employee', 'deductionStartPeriod', 'repayments.payslip'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return response()->json([
            'data' => $loan,
        ]);
    }

    public function approve(PayrollRequest $request, int $id): JsonResponse
    {
        $tenantId = $request->tenantId();
        $userId = $request->currentUserId();
        if ($userId === null) {
            throw new AuthenticationException('Authenticated user context is required.');
        }

        $loan = PayrollEmployeeLoan::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $approved = $this->loanService->approveLoan($loan, $userId);

        return response()->json([
            'message' => 'Loan approved successfully.',
            'data' => $approved->load(['employee', 'deductionStartPeriod']),
        ]);
    }

    public function disburse(PayrollRequest $request, int $id): JsonResponse
    {
        $tenantId = $request->tenantId();

        $validated = $request->validate([
            'disbursed_date' => ['required', 'date'],
            'deduction_start_period_id' => ['nullable', 'integer'],
        ]);

        $loan = PayrollEmployeeLoan::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $disbursed = $this->loanService->disburseLoan(
            $loan,
            $validated['disbursed_date'],
            isset($validated['deduction_start_period_id']) ? (int) $validated['deduction_start_period_id'] : null,
        );

        return response()->json([
            'message' => 'Loan disbursed successfully.',
            'data' => $disbursed->load(['employee', 'deductionStartPeriod']),
        ]);
    }

    public function cancel(PayrollRequest $request, int $id): JsonResponse
    {
        $tenantId = $request->tenantId();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $loan = PayrollEmployeeLoan::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $cancelled = $this->loanService->cancelLoan($loan, $validated['reason']);

        return response()->json([
            'message' => 'Loan cancelled successfully.',
            'data' => $cancelled,
        ]);
    }
}
