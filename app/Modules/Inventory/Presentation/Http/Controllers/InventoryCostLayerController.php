<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\CreateInventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\DeleteInventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\GetInventoryCostLayerServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\ListInventoryCostLayersServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\InventoryCostLayers\UpdateInventoryCostLayerServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListInventoryCostLayerRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertInventoryCostLayerRequest;
use Modules\Inventory\Presentation\Http\Resources\InventoryCostLayerResource;

final class InventoryCostLayerController extends Controller
{
    public function __construct(
        private readonly ListInventoryCostLayersServiceInterface $listService,
        private readonly GetInventoryCostLayerServiceInterface $getService,
        private readonly CreateInventoryCostLayerServiceInterface $createService,
        private readonly UpdateInventoryCostLayerServiceInterface $updateService,
        private readonly DeleteInventoryCostLayerServiceInterface $deleteService,
    ) {
    }

    public function index(ListInventoryCostLayerRequest $request): JsonResponse
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
            'data' => InventoryCostLayerResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|InventoryCostLayerResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new InventoryCostLayerResource($result->valueOrFail());
    }

    public function store(UpsertInventoryCostLayerRequest $request): JsonResponse|InventoryCostLayerResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new InventoryCostLayerResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertInventoryCostLayerRequest $request, int|string $id): JsonResponse|InventoryCostLayerResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new InventoryCostLayerResource($result->valueOrFail());
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