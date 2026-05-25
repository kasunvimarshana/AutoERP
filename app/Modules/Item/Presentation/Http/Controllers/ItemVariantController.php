<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\CreateItemVariantServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\DeleteItemVariantServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\GetItemVariantServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\ListItemVariantsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariants\UpdateItemVariantServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemVariantRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemVariantRequest;
use Modules\Item\Presentation\Http\Resources\ItemVariantResource;

final class ItemVariantController extends Controller
{
    public function __construct(
        private readonly ListItemVariantsServiceInterface $listService,
        private readonly GetItemVariantServiceInterface $getService,
        private readonly CreateItemVariantServiceInterface $createService,
        private readonly UpdateItemVariantServiceInterface $updateService,
        private readonly DeleteItemVariantServiceInterface $deleteService,
    ) {
    }

    public function index(ListItemVariantRequest $request): JsonResponse
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
            'data' => ItemVariantResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemVariantResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemVariantResource($result->valueOrFail());
    }

    public function store(UpsertItemVariantRequest $request): JsonResponse|ItemVariantResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemVariantResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemVariantRequest $request, int|string $id): JsonResponse|ItemVariantResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemVariantResource($result->valueOrFail());
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
