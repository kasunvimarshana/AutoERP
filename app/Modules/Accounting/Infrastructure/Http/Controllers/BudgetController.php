<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\BudgetServiceInterface;
use Modules\Accounting\Infrastructure\Http\Resources\BudgetResource;
class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetServiceInterface $budgetService,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $budgets  = $this->budgetService->getAllBudgets($tenantId);
        return response()->json(BudgetResource::collection($budgets));
    }
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $budget   = $this->budgetService->createBudget($tenantId, $request->all());
        return response()->json(new BudgetResource($budget), 201);
    }
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $budget   = $this->budgetService->getBudget($tenantId, $id);
        return response()->json(new BudgetResource($budget));
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $budget   = $this->budgetService->updateBudget($tenantId, $id, $request->all());
        return response()->json(new BudgetResource($budget));
    }
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->budgetService->deleteBudget($tenantId, $id);
        return response()->json(null, 204);
    }
}
