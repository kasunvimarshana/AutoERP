<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Returns\Application\Contracts\ApproveStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CancelStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CompleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CreateStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\DeleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\FindStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\RejectStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\UpdateStockReturnServiceInterface;
use Modules\Returns\Application\DTOs\StockReturnData;
use Modules\Returns\Application\DTOs\UpdateStockReturnData;
use Modules\Returns\Infrastructure\Http\Requests\StoreStockReturnRequest;
use Modules\Returns\Infrastructure\Http\Requests\UpdateStockReturnRequest;
use Modules\Returns\Infrastructure\Http\Resources\StockReturnCollection;
use Modules\Returns\Infrastructure\Http\Resources\StockReturnResource;

class StockReturnController extends AuthorizedController
{
    public function __construct(
        protected FindStockReturnServiceInterface $findService,
        protected CreateStockReturnServiceInterface $createService,
        protected UpdateStockReturnServiceInterface $updateService,
        protected DeleteStockReturnServiceInterface $deleteService,
        protected ApproveStockReturnServiceInterface $approveService,
        protected RejectStockReturnServiceInterface $rejectService,
        protected CompleteStockReturnServiceInterface $completeService,
        protected CancelStockReturnServiceInterface $cancelService,
        protected IssueCreditMemoServiceInterface $issueCreditMemoService,
    ) {}

    public function index(Request $request): StockReturnCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'return_type', 'party_id']);
        return new StockReturnCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreStockReturnRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = StockReturnData::fromArray([
            'tenantId'              => $v['tenant_id'],
            'referenceNumber'       => $v['reference_number'],
            'returnType'            => $v['return_type'],
            'partyId'               => $v['party_id'],
            'partyType'             => $v['party_type'],
            'originalReferenceId'   => $v['original_reference_id'] ?? null,
            'originalReferenceType' => $v['original_reference_type'] ?? null,
            'returnReason'          => $v['return_reason'] ?? null,
            'totalAmount'           => $v['total_amount'] ?? 0.0,
            'currency'              => $v['currency'] ?? 'USD',
            'restock'               => $v['restock'] ?? true,
            'restockLocationId'     => $v['restock_location_id'] ?? null,
            'restockingFee'         => $v['restocking_fee'] ?? 0.0,
            'notes'                 => $v['notes'] ?? null,
            'metadata'              => $v['metadata'] ?? null,
            'status'                => $v['status'] ?? 'draft',
        ]);

        $return = $this->createService->execute($dto->toArray());
        return (new StockReturnResource($return))->response()->setStatusCode(201);
    }

    public function show(int $id): StockReturnResource|JsonResponse
    {
        $return = $this->findService->find($id);
        if (! $return) { return response()->json(['message' => 'Not found'], 404); }
        return new StockReturnResource($return);
    }

    public function update(UpdateStockReturnRequest $request, int $id): StockReturnResource
    {
        $v   = $request->validated();
        $dto = UpdateStockReturnData::fromArray(array_merge(['id' => $id], [
            'returnReason' => $v['return_reason'] ?? null,
            'notes'        => $v['notes'] ?? null,
            'metadata'     => $v['metadata'] ?? null,
        ]));
        return new StockReturnResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Stock return deleted successfully']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $return = $this->approveService->execute([
            'id'          => $id,
            'approved_by' => $request->integer('approved_by'),
        ]);
        return (new StockReturnResource($return))->response();
    }

    public function reject(int $id): JsonResponse
    {
        $return = $this->rejectService->execute(['id' => $id]);
        return (new StockReturnResource($return))->response();
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $return = $this->completeService->execute([
            'id'           => $id,
            'processed_by' => $request->integer('processed_by'),
        ]);
        return (new StockReturnResource($return))->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $return = $this->cancelService->execute(['id' => $id]);
        return (new StockReturnResource($return))->response();
    }

    public function issueCreditMemo(Request $request, int $id): JsonResponse
    {
        $return = $this->issueCreditMemoService->execute([
            'id'                    => $id,
            'credit_memo_reference' => $request->string('credit_memo_reference'),
        ]);
        return (new StockReturnResource($return))->response();
    }
}
