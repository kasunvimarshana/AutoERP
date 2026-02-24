<?php

namespace Modules\Expense\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Expense\Application\UseCases\ApproveExpenseClaimUseCase;
use Modules\Expense\Application\UseCases\CreateExpenseClaimUseCase;
use Modules\Expense\Application\UseCases\ReimburseExpenseClaimUseCase;
use Modules\Expense\Application\UseCases\SubmitExpenseClaimUseCase;
use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;
use Modules\Expense\Presentation\Requests\ApproveExpenseClaimRequest;
use Modules\Expense\Presentation\Requests\StoreExpenseClaimRequest;

class ExpenseClaimController extends Controller
{
    public function __construct(
        private ExpenseClaimRepositoryInterface $claimRepo,
        private CreateExpenseClaimUseCase       $createUseCase,
        private SubmitExpenseClaimUseCase       $submitUseCase,
        private ApproveExpenseClaimUseCase      $approveUseCase,
        private ReimburseExpenseClaimUseCase    $reimburseUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->claimRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreExpenseClaimRequest $request): JsonResponse
    {
        $claim = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($claim, 201);
    }

    public function show(string $id): JsonResponse
    {
        $claim = $this->claimRepo->findById($id);

        if (! $claim) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($claim);
    }

    public function submit(string $id): JsonResponse
    {
        $claim = $this->submitUseCase->execute($id);

        return response()->json($claim);
    }

    public function approve(ApproveExpenseClaimRequest $request, string $id): JsonResponse
    {
        $claim = $this->approveUseCase->execute($id, $request->validated()['approver_id']);

        return response()->json($claim);
    }

    public function reimburse(string $id): JsonResponse
    {
        $claim = $this->reimburseUseCase->execute($id);

        return response()->json($claim);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->claimRepo->delete($id);

        return response()->json(null, 204);
    }
}
