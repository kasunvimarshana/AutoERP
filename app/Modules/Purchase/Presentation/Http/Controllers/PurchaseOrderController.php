<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\GetPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\ListPurchaseOrdersServiceInterface;
use Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Presentation\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseOrderRequest;
use Modules\Purchase\Presentation\Http\Resources\PurchaseOrderResource;

final class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly ListPurchaseOrdersServiceInterface $listService,
        private readonly GetPurchaseOrderServiceInterface $getService,
        private readonly CreatePurchaseOrderServiceInterface $createService,
        private readonly UpdatePurchaseOrderServiceInterface $updateService,
        private readonly DeletePurchaseOrderServiceInterface $deleteService,
    ) {
    }

    public function index(ListPurchaseOrderRequest $request): JsonResponse
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
            'data' => PurchaseOrderResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PurchaseOrderResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PurchaseOrderResource($result->valueOrFail());
    }

    public function store(UpsertPurchaseOrderRequest $request): JsonResponse|PurchaseOrderResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PurchaseOrderResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPurchaseOrderRequest $request, int|string $id): JsonResponse|PurchaseOrderResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PURCHASE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PurchaseOrderResource($result->valueOrFail());
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