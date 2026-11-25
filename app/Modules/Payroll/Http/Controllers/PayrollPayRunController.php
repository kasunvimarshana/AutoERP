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
use Modules\Payroll\Enums\PayRunType;
use Modules\Payroll\Http\Requests\PayrollRequest;
use Modules\Payroll\Models\PayrollPayRun;
use Modules\Payroll\Services\PayRunExecutionService;

final class PayrollPayRunController
{
    public function __construct(
        private readonly PayRunExecutionService $executionService,
    ) {}

    public function index(PayrollRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();
        $query = PayrollPayRun::query()
            ->with(['period', 'structure', 'calculatedByUser', 'approvedByUser'])
            ->where('tenant_id', $tenantId);

        if ($request->has('pay_period_id')) {
            $query->where('pay_period_id', (int) $request->input('pay_period_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));

        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(PayrollRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'pay_period_id' => ['required', 'integer'],
            'salary_structure_id' => ['nullable', 'integer'],
            'pay_run_type' => ['nullable', 'string', Rule::in(PayRunType::values())],
            'notes' => ['nullable', 'string'],
        ]);

        $payRun = $this->executionService->createPayRun(
            tenantId: $request->tenantId(),
            payPeriodId: $validated['pay_period_id'],
            structureId: $validated['salary_structure_id'] ?? null,
            type: $validated['pay_run_type'] ?? 'standard',
            notes: $validated['notes'] ?? null,
            userId: $request->currentUserId(),
        );

        return response()->json(['data' => $payRun->fresh(['period', 'structure'])], 201);
    }

    public function show(PayrollRequest $request, int $id): JsonResponse
    {
        $payRun = PayrollPayRun::query()
            ->with(['period', 'structure', 'payslips.employee', 'calculatedByUser', 'approvedByUser', 'paidByUser', 'reversedByUser', 'financeJournal'])
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($id);

        return response()->json(['data' => $payRun]);
    }

    public function calculate(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $payRun = PayrollPayRun::query()->where('tenant_id', $request->tenantId())->findOrFail($id);
        $userId = (int) $request->currentUserId();
        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = $this->executionService->calculatePayRun((int) $payRun->id, $userId, $expectedVersion);

        return response()->json([
            'message' => 'Pay run calculated successfully.',
            'data' => $updated,
        ]);
    }

    public function approve(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $payRun = PayrollPayRun::query()->where('tenant_id', $request->tenantId())->findOrFail($id);
        $userId = (int) $request->currentUserId();
        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = $this->executionService->approvePayRun((int) $payRun->id, $userId, $expectedVersion);

        return response()->json([
            'message' => 'Pay run approved successfully.',
            'data' => $updated,
        ]);
    }

    public function pay(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $payRun = PayrollPayRun::query()->where('tenant_id', $request->tenantId())->findOrFail($id);
        $userId = (int) $request->currentUserId();
        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = $this->executionService->payPayRun((int) $payRun->id, $userId, $expectedVersion);

        return response()->json([
            'message' => 'Pay run finalized and posted to General Ledger.',
            'data' => $updated,
        ]);
    }

    public function reverse(PayrollRequest $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $payRun = PayrollPayRun::query()->where('tenant_id', $request->tenantId())->findOrFail($id);
        $userId = (int) $request->currentUserId();
        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;

        $updated = $this->executionService->reversePayRun((int) $payRun->id, $userId, $validated['reason'], $expectedVersion);

        return response()->json([
            'message' => 'Pay run and linked GL journals reversed successfully.',
            'data' => $updated,
        ]);
    }
}
