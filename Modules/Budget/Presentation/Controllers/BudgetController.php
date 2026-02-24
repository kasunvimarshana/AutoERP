<?php

namespace Modules\Budget\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Budget\Application\UseCases\ApproveBudgetUseCase;
use Modules\Budget\Application\UseCases\CloseBudgetUseCase;
use Modules\Budget\Application\UseCases\CreateBudgetUseCase;
use Modules\Budget\Application\UseCases\GetBudgetVarianceReportUseCase;
use Modules\Budget\Application\UseCases\RecordActualSpendUseCase;
use Modules\Budget\Domain\Contracts\BudgetLineRepositoryInterface;
use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Presentation\Requests\RecordActualSpendRequest;
use Modules\Budget\Presentation\Requests\StoreBudgetRequest;
use Modules\Shared\Application\ResponseFormatter;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetRepositoryInterface     $budgetRepo,
        private BudgetLineRepositoryInterface $lineRepo,
        private CreateBudgetUseCase           $createUseCase,
        private ApproveBudgetUseCase          $approveUseCase,
        private CloseBudgetUseCase            $closeUseCase,
        private RecordActualSpendUseCase      $recordActualSpendUseCase,
        private GetBudgetVarianceReportUseCase $varianceReportUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->budgetRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $budget = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($budget, 201);
    }

    public function show(string $id): JsonResponse
    {
        $budget = $this->budgetRepo->findById($id);

        if (! $budget) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json([
            'budget' => $budget,
            'lines'  => $this->lineRepo->findByBudget($id),
        ]);
    }

    public function update(StoreBudgetRequest $request, string $id): JsonResponse
    {
        $budget = $this->budgetRepo->update($id, $request->validated());

        return response()->json($budget);
    }

    public function approve(string $id): JsonResponse
    {
        $budget = $this->approveUseCase->execute($id, auth()->user()?->id ?? '');

        return response()->json($budget);
    }

    public function close(string $id): JsonResponse
    {
        $budget = $this->closeUseCase->execute($id);

        return response()->json($budget);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->budgetRepo->delete($id);

        return response()->json(null, 204);
    }

    public function varianceReport(string $id): JsonResponse
    {
        $report = $this->varianceReportUseCase->execute($id);

        return ResponseFormatter::success($report);
    }

    public function recordActualSpend(RecordActualSpendRequest $request, string $id, string $lineId): JsonResponse
    {
        $line = $this->recordActualSpendUseCase->execute(
            $id,
            $lineId,
            (string) $request->validated('amount'),
        );

        return ResponseFormatter::success($line, 'Actual spend recorded.');
    }
}
