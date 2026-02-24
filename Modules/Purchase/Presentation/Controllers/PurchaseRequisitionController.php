<?php

namespace Modules\Purchase\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\UseCases\ApprovePurchaseRequisitionUseCase;
use Modules\Purchase\Application\UseCases\ConvertRequisitionToPurchaseOrderUseCase;
use Modules\Purchase\Application\UseCases\CreatePurchaseRequisitionUseCase;
use Modules\Purchase\Application\UseCases\RejectPurchaseRequisitionUseCase;
use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Presentation\Requests\RejectPurchaseRequisitionRequest;
use Modules\Purchase\Presentation\Requests\StorePurchaseRequisitionRequest;
use Modules\Purchase\Presentation\Requests\ConvertRequisitionToPurchaseOrderRequest;

class PurchaseRequisitionController extends Controller
{
    public function __construct(
        private PurchaseRequisitionRepositoryInterface  $repo,
        private CreatePurchaseRequisitionUseCase        $createUseCase,
        private ApprovePurchaseRequisitionUseCase       $approveUseCase,
        private RejectPurchaseRequisitionUseCase        $rejectUseCase,
        private ConvertRequisitionToPurchaseOrderUseCase $convertUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(
            $this->repo->paginate(request()->only(['status', 'requested_by', 'department']))
        );
    }

    public function store(StorePurchaseRequisitionRequest $request): JsonResponse
    {
        $requisition = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($requisition, 201);
    }

    public function show(string $id): JsonResponse
    {
        $requisition = $this->repo->findById($id);

        if (! $requisition) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($requisition);
    }

    public function update(StorePurchaseRequisitionRequest $request, string $id): JsonResponse
    {
        $requisition = $this->repo->update($id, $request->validated());

        return response()->json($requisition);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);

        return response()->json(null, 204);
    }

    public function approve(string $id): JsonResponse
    {
        $requisition = $this->approveUseCase->execute($id);

        return response()->json($requisition);
    }

    public function reject(RejectPurchaseRequisitionRequest $request, string $id): JsonResponse
    {
        $requisition = $this->rejectUseCase->execute($id, $request->validated()['reason'] ?? null);

        return response()->json($requisition);
    }

    public function convertToPo(ConvertRequisitionToPurchaseOrderRequest $request, string $id): JsonResponse
    {
        $po = $this->convertUseCase->execute($id, $request->validated());

        return response()->json($po, 201);
    }
}
