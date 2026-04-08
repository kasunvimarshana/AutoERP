<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Returns\Application\Contracts\ApplyCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\CreateCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\DeleteCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\FindCreditMemoServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoDocumentServiceInterface;
use Modules\Returns\Application\Contracts\VoidCreditMemoServiceInterface;
use Modules\Returns\Application\DTOs\CreditMemoData;
use Modules\Returns\Infrastructure\Http\Requests\StoreCreditMemoRequest;
use Modules\Returns\Infrastructure\Http\Resources\CreditMemoCollection;
use Modules\Returns\Infrastructure\Http\Resources\CreditMemoResource;

class CreditMemoController extends AuthorizedController
{
    public function __construct(
        protected FindCreditMemoServiceInterface $findService,
        protected CreateCreditMemoServiceInterface $createService,
        protected DeleteCreditMemoServiceInterface $deleteService,
        protected IssueCreditMemoDocumentServiceInterface $issueService,
        protected ApplyCreditMemoServiceInterface $applyService,
        protected VoidCreditMemoServiceInterface $voidService,
    ) {}

    public function index(Request $request): CreditMemoCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'party_id', 'stock_return_id']);
        return new CreditMemoCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreCreditMemoRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = CreditMemoData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'referenceNumber' => $v['reference_number'],
            'partyId'         => $v['party_id'],
            'partyType'       => $v['party_type'],
            'stockReturnId'   => $v['stock_return_id'] ?? null,
            'amount'          => $v['amount'] ?? 0.0,
            'currency'        => $v['currency'] ?? 'USD',
            'notes'           => $v['notes'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
            'status'          => $v['status'] ?? 'draft',
        ]);

        $memo = $this->createService->execute($dto->toArray());
        return (new CreditMemoResource($memo))->response()->setStatusCode(201);
    }

    public function show(int $id): CreditMemoResource|JsonResponse
    {
        $memo = $this->findService->find($id);
        if (! $memo) { return response()->json(['message' => 'Not found'], 404); }
        return new CreditMemoResource($memo);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Credit memo deleted successfully']);
    }

    public function issue(int $id): JsonResponse
    {
        $memo = $this->issueService->execute(['id' => $id]);
        return (new CreditMemoResource($memo))->response();
    }

    public function apply(int $id): JsonResponse
    {
        $memo = $this->applyService->execute(['id' => $id]);
        return (new CreditMemoResource($memo))->response();
    }

    public function void(int $id): JsonResponse
    {
        $memo = $this->voidService->execute(['id' => $id]);
        return (new CreditMemoResource($memo))->response();
    }
}
