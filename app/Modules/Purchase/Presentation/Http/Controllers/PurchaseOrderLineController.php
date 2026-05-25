<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\CreatePurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\DeletePurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\GetPurchaseOrderLineServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\ListPurchaseOrderLinesServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines\UpdatePurchaseOrderLineServiceInterface;
use Modules\Purchase\Presentation\Http\Requests\ListPurchaseOrderLineRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseOrderLineRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseOrderLineResource;

final class PurchaseOrderLineController extends Controller
{
    public function __construct(
        private readonly ListPurchaseOrderLinesServiceInterface $listService,
        private readonly GetPurchaseOrderLineServiceInterface $getService,
        private readonly CreatePurchaseOrderLineServiceInterface $createService,
        private readonly UpdatePurchaseOrderLineServiceInterface $updateService,
        private readonly DeletePurchaseOrderLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListPurchaseOrderLineRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => PurchaseOrderLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PurchaseOrderLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PurchaseOrderLineResource($result->valueOrFail());
    }

    public function store(UpsertPurchaseOrderLineRequest $request): JsonResponse|PurchaseOrderLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PurchaseOrderLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPurchaseOrderLineRequest $request, int|string $id): JsonResponse|PurchaseOrderLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PURCHASE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PurchaseOrderLineResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}