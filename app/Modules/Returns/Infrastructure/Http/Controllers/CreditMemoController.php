<?php

namespace Modules\Returns\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Returns\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoDirectServiceInterface;
use Modules\Returns\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Returns\Application\DTOs\CreditMemoData;
use Modules\Returns\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Returns\Infrastructure\Http\Resources\CreditMemoResource;

class CreditMemoController extends Controller
{
    public function __construct(
        private readonly CreditMemoRepositoryInterface $repository,
        private readonly CreateCreditMemoServiceInterface $createService,
        private readonly IssueCreditMemoDirectServiceInterface $issueService,
        private readonly ApplyCreditMemoServiceInterface $applyService,
        private readonly VoidCreditMemoServiceInterface $voidService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $memos = $this->repository->findAll(
            $tenantId,
            $request->only(['status', 'customer_id', 'stock_return_id'])
        );

        return response()->json($memos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'       => 'required|integer',
            'stock_return_id' => 'required|integer',
            'memo_number'     => 'required|string|max:100',
            'amount'          => 'required|numeric|min:0',
            'customer_id'     => 'nullable|integer',
            'currency'        => 'nullable|string|size:3',
            'notes'           => 'nullable|string',
        ]);

        try {
            $dto = new CreditMemoData(
                tenantId: $validated['tenant_id'],
                stockReturnId: $validated['stock_return_id'],
                memoNumber: $validated['memo_number'],
                amount: (float) $validated['amount'],
                customerId: $validated['customer_id'] ?? null,
                currency: $validated['currency'] ?? 'USD',
                notes: $validated['notes'] ?? null,
            );

            $memo = $this->createService->execute($dto);

            return response()->json(new CreditMemoResource($memo), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $memo = $this->repository->findById($id);
        if (!$memo) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(new CreditMemoResource($memo));
    }

    public function issue(Request $request, int $id): JsonResponse
    {
        $request->validate(['issued_by' => 'required|integer']);

        $memo = $this->repository->findById($id);
        if (!$memo) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->issueService->execute($memo, (int) $request->input('issued_by'));

            return response()->json(new CreditMemoResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function apply(int $id): JsonResponse
    {
        $memo = $this->repository->findById($id);
        if (!$memo) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->applyService->execute($memo);

            return response()->json(new CreditMemoResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function void(int $id): JsonResponse
    {
        $memo = $this->repository->findById($id);
        if (!$memo) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->voidService->execute($memo);

            return response()->json(new CreditMemoResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
